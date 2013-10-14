# import to process args
import sys
import os
import math
import json

try:
    import argparse
except ImportError:
    # since  Python 2.6 and earlier don't have argparse, we simply provide
    # the source for the same as _argparse and we use it instead.
    import _argparse as argparse

# import annotations
from autobahn.wamp import exportRpc

# import paraview modules.
from paraview import simple, servermanager
from paraview.web import protocols, wamp
from vtk.web import server

# import utility methods
import pvw_utils

# Setup global variables
timesteps = []
currentTimeIndex = 0
view = None
dataPath = None
authKey = None

def initView(width, height):
  global view
  view = simple.GetRenderView()
  simple.Render()
  view.ViewSize = [width, height]
  view.Background = [1, 1, 1]
  view.OrientationAxesLabelColor = [0, 0, 0]

  print 'View created successfully (%dx%d)' % (width, height)


# This class defines the exposed RPC methods for the midas application
class MidasApp(wamp.PVServerProtocol):
  DISTANCE_FACTOR = 2.0
  CONTOUR_LINE_WIDTH = 2.0

  colorArrayName = None
  sof = None
  lookupTable = None
  srcObj = None
  bounds = None
  extent = None
  center = None
  centerExtent = None
  rep = None
  scalarRange = None
  imageData = None
  subgrid = None
  sliceMode = None
  meshSlice = None
  sphere = None
  labelmapOpacity = None
  surfaces = []
  labelmaps = []
  canvasObj = None # painting volume
  canvasRep = None # representation for self.canvasObj

  def initialize(self):
    global authKey

    # Bring used components
    self.registerVtkWebProtocol(protocols.ParaViewWebMouseHandler())
    self.registerVtkWebProtocol(protocols.ParaViewWebViewPort())
    self.registerVtkWebProtocol(protocols.ParaViewWebViewPortImageDelivery())
    self.registerVtkWebProtocol(protocols.ParaViewWebViewPortGeometryDelivery())

    # Update authentication key to use
    self.updateSecret(authKey)

  def _extractVolumeImageData(self):
    if(self.srcObj.GetPointDataInformation().GetNumberOfArrays() == 0):
      print 'Error: no data information arrays'
      raise Exception('No data information arrays')

    self.imageData = self.srcObj.GetPointDataInformation().GetArray(0)
    self.colorArrayName = self.imageData.Name
    self.scalarRange = self.imageData.GetRange()
    self.bounds = self.srcObj.GetDataInformation().DataInformation.GetBounds()
    self.extent = self.srcObj.GetDataInformation().DataInformation.GetExtent()
    self.center = [(self.bounds[1] + self.bounds[0]) / 2.0,
                   (self.bounds[3] + self.bounds[2]) / 2.0,
                   (self.bounds[5] + self.bounds[4]) / 2.0]
    self.centerExtent = [(self.extent[1] + self.extent[0]) / 2.0,
                         (self.extent[3] + self.extent[2]) / 2.0,
                         (self.extent[5] + self.extent[4]) / 2.0]

  def _getLabelmapLookupTable(self):
      """Fixed LookupTable for all labelmaps"""
      proxyName = "%d.%s.PVLookupTable" % (1, 'LabelMap')
      lookupTable = servermanager.ProxyManager().GetProxy("lookup_tables", proxyName)
      if lookupTable:
          return lookupTable
      # Use Slicer's 'GenericAnatomyColors' preset colors
      rgbPoints = [0.0, 0.0, 0.0, 0.0,
                   1.0, 0.501960784, 0.682352941, 0.501960784,
                   2.0, 0.945098039, 0.839215686, 0.568627451,
                   3.0, 0.694117647, 0.478431373, 0.396078431,
                   4.0, 0.435294118, 0.721568627, 0.82120566221,
                   5.0, 0.847058824, 0.396078431, 0.309803922,
                   6.0, 0.866666667, 0.509803922, 0.396078431,
                   7.0, 0.564705882, 0.933333333, 0.564705882,
                   8.0, 0.752941176, 0.407843137, 0.345098039,
                   9.0, 0.862745098, 0.960784314, 0.078431373,
                   10.0, 0.305882353, 0.247058824, 0.0,
                   11.0, 1.0, 0.980392157, 0.862745098,
                   12.0, 0.901960784, 0.862745098, 0.274509804]
      lookupTable = servermanager.rendering.PVLookupTable(
            ColorSpace="RGB", RGBPoints=rgbPoints)
      servermanager.Register(lookupTable, registrationName=proxyName)
      lookupTable.ScalarRangeInitialized = 1.0
      lookupTable.EnableOpacityMapping = 1
      lookupTable.ScalarOpacityFunction = self._getScalarOpacityFunction()
      return lookupTable

  def _getScalarOpacityFunction(self):
       """Fixed Opacity Transfer Function for all labelmaps"""
       sofPoints = [0.0, 0.0, 0.5, 0.0,
                    1.0, 1.0, 0.5, 0.0,
                    2.0, 1.0, 0.5, 0.0,
                    3.0, 1.0, 0.5, 0.0,
                    4.0, 1.0, 0.5, 0.0,
                    5.0, 1.0, 0.5, 0.0,
                    6.0, 1.0, 0.5, 0.0,
                    7.0, 1.0, 0.5, 0.0,
                    8.0, 1.0, 0.5, 0.0,
                    9.0, 1.0, 0.5, 0.0,
                    10.0, 1.0, 0.5, 0.0,
                    11.0, 1.0, 0.5, 0.0,
                    12.0, 1.0, 0.5, 0.0]
       return simple.CreatePiecewiseFunction(Points=sofPoints)

  def _setLabelmapsLookupTable(self):
    for labelmap in self.labelmaps:
        if(labelmap.GetPointDataInformation().GetNumberOfArrays() == 0):
            print 'Error: no data information arrays'
            raise Exception('No data information arrays')
        imageData = labelmap.GetPointDataInformation().GetArray(0)
        colorArrayName = imageData.Name
        scalarRange = imageData.GetRange()
        rep = simple.Show(labelmap)
        rep.ColorArrayName = self.colorArrayName
        rep.LookupTable = self._getLabelmapLookupTable()
        rep.ScalarOpacityFunction = rep.LookupTable.ScalarOpacityFunction

    simple.SetActiveSource(self.srcObj)

  def _loadSurfaceWithProperties(self, fullpath):
    if not fullpath.endswith('.properties'):
      surfaceObj = simple.OpenDataFile(fullpath)
      self.surfaces.append(surfaceObj)
      rep = simple.Show()
      rep.Representation = 'Surface'

      # If a corresponding properties file exists, load it in
      # and apply the properties to the surface
      if os.path.isfile(fullpath+'.properties'):
        with open(fullpath+'.properties') as f:
          lines = f.readlines()
          for line in lines:
            (property, value) = line.split(' ', 1)
            if hasattr(rep, property):
              value = json.loads(value.strip())
              setattr(rep, property, value)
            else:
              print 'Skipping invalid property %s' % property

      print 'Loaded surface %s into scene' % fullpath

  def _loadLabelmaps(self, fullpath):
    "Load label maps from input data directory"
    if not fullpath.endswith('.properties'):
        labelmapObj = simple.OpenDataFile(fullpath)
        self.labelmaps.append(labelmapObj)
        rep = simple.Show()
        rep.Representation = 'Slice'
    print 'Loaded labelmap %s into scene' % fullpath

  def _sliceSurfaces(self, slice):
    if self.meshSlice is not None:
      simple.Delete(self.meshSlice)
      self.meshSlice = None

    for surface in self.surfaces:
      rep = simple.Show(surface)

      if self.sliceMode == 'XY Plane':
        origin = [0.0, 0.0, math.cos(math.radians(rep.Orientation[2]))*slice]
        normal = [0.0, 0.0, 1.0]
      elif self.sliceMode == 'XZ Plane':
        origin = [0.0, math.cos(math.radians(rep.Orientation[1]))*slice, 0.0]
        normal = [0.0, 1.0, 0.0]
      else:
        origin = [math.cos(math.radians(rep.Orientation[0]))*slice, 0.0, 0.0]
        normal = [1.0, 0.0, 0.0]

      simple.Hide(surface)
      self.meshSlice = simple.Slice(Input=surface, SliceType='Plane')
      simple.SetActiveSource(self.srcObj)

      self.meshSlice.SliceOffsetValues = [0.0]
      self.meshSlice.SliceType = 'Plane'
      self.meshSlice.SliceType.Origin = origin
      self.meshSlice.SliceType.Normal = normal
      meshDataRep = simple.Show(self.meshSlice)

      meshDataRep.Representation = 'Points'
      meshDataRep.LineWidth = self.CONTOUR_LINE_WIDTH
      meshDataRep.PointSize = self.CONTOUR_LINE_WIDTH
      meshDataRep.AmbientColor = rep.DiffuseColor
      meshDataRep.Orientation = rep.Orientation

    simple.SetActiveSource(self.srcObj)

  def _sliceLabelmaps(self, slice):
    "Display input label maps"
    for labelmap in self.labelmaps:
        rep = simple.Show(labelmap)
        rep.Slice = slice
        rep.SliceMode = self.sliceMode
        # Opacity slider only supports one label map
        self.labelmapOpacity = rep.Opacity
    simple.SetActiveSource(self.srcObj)

  def _sliceCanvas(self, slice):
    "Display painting volume in slice mode"
    if self.canvasRep is not None:
        self.canvasRep.Visibility = 1
        self.canvasRep.Slice = slice
        self.canvasRep.SliceMode = self.sliceMode

        simple.SetActiveSource(self.srcObj)

  @exportRpc("loadData")
  def loadData(self):
    global dataPath
    mainpath = os.path.join(dataPath, "main")

    if os.path.isdir(mainpath):
      files = os.listdir(mainpath)
      for file in files:
        fullpath = os.path.join(mainpath, file)
        if os.path.isfile(fullpath):
          self.srcObj = simple.OpenDataFile(fullpath)
          simple.SetActiveSource(self.srcObj)
          self.rep = simple.GetDisplayProperties()
          simple.Hide()

          print 'Loaded %s into scene' % fullpath
    else:
      print 'Error: '+mainpath+' does not exist\n'
      raise Exception("The main directory does not exist")

    surfacespath = os.path.join(dataPath, "surfaces")
    files = os.listdir(surfacespath)
    for file in files:
      fullpath = os.path.join(surfacespath, file)
      if os.path.isfile(fullpath):
        self._loadSurfaceWithProperties(fullpath)

    labempaspath = os.path.join(dataPath, "labelmaps")
    files = os.listdir(labempaspath)
    for file in files:
      fullpath = os.path.join(labempaspath, file)
      if os.path.isfile(fullpath):
        self._loadLabelmaps(fullpath)

    simple.SetActiveSource(self.srcObj)
    simple.ResetCamera()
    simple.Render()

  @exportRpc("exportCanvas")
  def exportCanvas(self, user_email, api_key, midas_url, file_name, output_folder_id):
      """Export painting out of Paraview to local disk and then upload it
      into the Midas server using Pydas"""
      global dataPath
      pydasParams = (user_email, api_key, midas_url)
      if self.canvasObj is not None:
          output_dir = os.path.join(dataPath, "output")
          output_file_path = os.path.join(output_dir, file_name)
          # Export painting to local disk
          writer = simple.CreateWriter(output_file_path, self.canvasObj)
          writer.UpdatePipeline()
          del writer
          # ParaView can only save MetaImage in .mhd format
          # Call utility function to convert it to .mha format
          if file_name.endswith('.mhd'):
              pvw_utils.mhdToMha(output_file_path)
              file_name = file_name[:-4] + '.mha'
          pvw_instance_id = os.path.basename(dataPath)
          # Upload file to the Midas server using Pydas
          labelmap_item_id = pvw_utils.uploadItem(pydasParams, file_name, output_folder_id, output_dir, out_file=file_name)
          return {'labelmap_item_id': labelmap_item_id}
      else:
          print 'Error: No canvas object is running'
          raise Exception('Error: No canvas object is running')

  @exportRpc("showSphere")
  def showSphere(self, params):
    if self.sphere is not None:
      simple.Delete(self.sphere)

    maxDim = max(self.bounds[1] - self.bounds[0],
                 self.bounds[3] - self.bounds[2],
                 self.bounds[5] - self.bounds[4])

    self.sphere = simple.Sphere()
    self.sphere.Radius = maxDim / 100.0
    self.sphere.Center = params['point']
    rep = simple.Show()
    rep.Representation = 'Surface'
    rep.DiffuseColor = params['color']

    simple.SetActiveSource(self.srcObj)

  @exportRpc("cameraPreset")
  def cameraPreset(self, direction):
    global view
    (midx, midy, midz) = (self.center[0], self.center[1], self.center[2])
    (lenx, leny, lenz) = (self.bounds[1] - self.bounds[0],
                          self.bounds[3] - self.bounds[2],
                          self.bounds[5] - self.bounds[4])
    maxDim = max(lenx, leny, lenz)

    view.CameraFocalPoint = self.center
    view.CenterOfRotation = self.center
    view.CameraViewUp = [0, 0, 1]

    if(direction == '+x'):
      view.CameraPosition = [midx - self.DISTANCE_FACTOR * maxDim, midy, midz]
    elif(direction == '-x'):
      view.CameraPosition = [midx + self.DISTANCE_FACTOR * maxDim, midy, midz]
    elif(direction == '+y'):
      view.CameraPosition = [midx, midy - self.DISTANCE_FACTOR * maxDim, midz]
    elif(direction == '-y'):
      view.CameraPosition = [midx, midy + self.DISTANCE_FACTOR * maxDim, midz]
    elif(direction == '+z'):
      view.CameraPosition = [midx, midy, midz - self.DISTANCE_FACTOR * maxDim]
      view.CameraViewUp = [0, 1, 0]
    elif(direction == '-z'):
      view.CameraPosition = [midx, midy, midz + self.DISTANCE_FACTOR * maxDim]
      view.CameraViewUp = [0, 1, 0]
    else:
      print "Invalid preset direction: %s" % direction

    simple.Render()

  @exportRpc("setSliceMode")
  def setSliceMode(self, sliceMode):
    global view
    if type(sliceMode) is unicode:
      sliceMode = sliceMode.encode('ascii', 'ignore')

    if(sliceMode == 'XY Plane'):
      sliceNum = int(math.floor(self.centerExtent[2]))
      cameraParallelScale = max(self.bounds[1] - self.bounds[0],
                                self.bounds[3] - self.bounds[2]) / 2.0
      cameraPosition = [self.center[0], self.center[1], self.bounds[4] - 10]
      maxSlices = self.extent[5] - self.extent[4]
      cameraViewUp = [0, -1, 0]
    elif(sliceMode == 'XZ Plane'):
      sliceNum = int(math.floor(self.centerExtent[1]))
      cameraParallelScale = max(self.bounds[1] - self.bounds[0],
                                self.bounds[5] - self.bounds[4]) / 2.0
      maxSlices = self.extent[3] - self.extent[2]
      cameraPosition = [self.center[0], self.bounds[3] + 10, self.center[2]]
      cameraViewUp = [0, 0, 1]
    elif(sliceMode == 'YZ Plane'):
      sliceNum = int(math.floor(self.centerExtent[0]))
      cameraParallelScale = max(self.bounds[3] - self.bounds[2],
                                self.bounds[5] - self.bounds[4]) / 2.0
      maxSlices = self.extent[1] - self.extent[0]
      cameraPosition = [self.bounds[1] + 10, self.center[1], self.center[2]]
      cameraViewUp = [0, 0, 1]
    else:
      print 'Error: invalid slice mode %s' % sliceMode
      raise Exception('Error: invalid slice mode %s' % sliceMode)

    view.CameraParallelScale = cameraParallelScale
    view.CameraViewUp = cameraViewUp
    view.CameraPosition = cameraPosition

    self.rep.Slice = sliceNum
    self.rep.SliceMode = sliceMode
    if self.canvasRep is not None:
        self.canvasRep.SliceMode = sliceMode

    self.sliceMode = sliceMode
    self._sliceLabelmaps(sliceNum)
    # TODO calculate slice plane origin for surfaces!!!
    self._sliceSurfaces(sliceNum)
    simple.Render()
    return {'slice': sliceNum,
            'maxSlices': maxSlices,
            'labelmapOpacity': self.labelmapOpacity,
            'cameraParallelScale': cameraParallelScale}

  @exportRpc("changeSlice")
  def changeSlice(self, sliceNum):
    self.rep.Slice = sliceNum
    self._sliceSurfaces(sliceNum)
    self._sliceLabelmaps(sliceNum)
    self._sliceCanvas(sliceNum)
    simple.Render()

  @exportRpc("changeLabelmapOpacity")
  def changeLabelmapOpacity(self, opacity):
    if self.labelmaps:
        # Opacity slider only supports one label map
        labelmapRep = simple.Show(self.labelmaps[-1])
        labelmapRep.Opacity = opacity
        simple.Render()


  @exportRpc("changeWindow")
  def changeWindow(self, points):
    self.lookupTable.RGBPoints = points
    simple.Render()

  @exportRpc("changeBgColor")
  def changeBgColor(self, rgb):
    global view
    view.Background = rgb

    if (sum(rgb) / 3.0) > 0.5:
      view.OrientationAxesLabelColor = [0, 0, 0]
    else:
      view.OrientationAxesLabelColor = [1, 1, 1]
    simple.Render()

  @exportRpc("changeCanvas")
  def changeCanvas(self, labelmap, sliceNum):
    """Update painting volume"""
    # Use python programmable filter
    if self.canvasObj is None:
        self.canvasObj = simple.ProgrammableFilter(self.srcObj)
    if labelmap is None:
        labelmap = []
    script = 'labelmap = %s\n' % labelmap
    script += 'input = self.GetInputDataObject(0, 0)\n'
    script += 'output = self.GetOutputDataObject(0)\n'
    script += 'numPoints = input.GetNumberOfPoints()\n'
    script += 'output.ShallowCopy(input)\n'
    # Label map can use vtkUnsignedShortArray no matter the input array type
    script += 'labelScalars = vtk.vtkUnsignedShortArray()\n'
    script += 'labelScalars.SetName("%s")\n' % self.colorArrayName
    script += 'labelScalars.SetNumberOfTuples(numPoints)\n'
    script += 'labelScalars.FillComponent(0, 0)\n'
    script += 'for label in labelmap:\n  labelScalars.SetValue(label[0], label[1])\n'
    script += 'output.GetPointData().AddArray(labelScalars)\n'
    script += 'output.GetPointData().SetScalars(labelScalars)'
    self.canvasObj.Script = script

    if self.canvasRep is None:
        self.canvasRep = simple.Show(self.canvasObj)
        self.canvasRep.SelectionPointFieldDataArrayName = self.colorArrayName
        self.canvasRep.Representation = 'Slice'
        self.canvasRep.SliceMode = self.sliceMode
        self.canvasRep.ColorArrayName = self.colorArrayName
        self.canvasRep.LookupTable = self._getLabelmapLookupTable()
        self.canvasRep.ScalarOpacityFunction = self.canvasRep.LookupTable.ScalarOpacityFunction
    self.canvasRep.Slice = sliceNum

    simple.SetActiveSource(self.srcObj)

  @exportRpc("surfaceRender")
  def surfaceRender(self):
    self.bounds = self.srcObj.GetDataInformation().DataInformation.GetBounds()
    self.center = [(self.bounds[1] + self.bounds[0]) / 2.0,
                   (self.bounds[3] + self.bounds[2]) / 2.0,
                   (self.bounds[5] + self.bounds[4]) / 2.0]

    self.cameraPreset('+x')
    self.rep.Representation = 'Surface'
    nbPoints = self.srcObj.GetDataInformation().GetNumberOfPoints()
    nbCells = self.srcObj.GetDataInformation().GetNumberOfCells()

    simple.Show()
    simple.Render()
    return {'bounds': self.bounds,
            'nbPoints': nbPoints,
            'nbCells': nbCells}


  @exportRpc("toggleEdges")
  def toggleEdges(self):
    if self.rep.Representation == 'Surface':
      self.rep.Representation = 'Surface With Edges'
    else:
      self.rep.Representation = 'Surface'

    simple.Render()


  @exportRpc("sliceRender")
  def sliceRender(self, sliceMode):
    global view
    self._extractVolumeImageData()
    (midx, midy, midz) = (self.center[0], self.center[1], self.center[2])
    (lenx, leny, lenz) = (self.bounds[1] - self.bounds[0],
                          self.bounds[3] - self.bounds[2],
                          self.bounds[5] - self.bounds[4])
    maxDim = max(lenx, leny, lenz)
    # Adjust camera properties appropriately
    view.Background = [0, 0, 0]
    view.CameraFocalPoint = self.center
    view.CenterOfRotation = self.center
    view.CenterAxesVisibility = False
    view.OrientationAxesVisibility = False
    view.CameraParallelProjection = True

    # Configure data representation
    rgbPoints = [self.scalarRange[0], 0, 0, 0, self.scalarRange[1], 1, 1, 1]
    self.lookupTable = simple.GetLookupTableForArray(self.colorArrayName, 1)
    self.lookupTable.RGBPoints = rgbPoints
    self.lookupTable.ScalarRangeInitialized = 1.0
    self.lookupTable.ColorSpace = 'RGB'

    self.rep.ColorArrayName = self.colorArrayName
    self.rep.Representation = 'Slice'
    self.rep.LookupTable = self.lookupTable

    # Set LookupTables for labelmaps
    self._setLabelmapsLookupTable()

    sliceInfo = self.setSliceMode(sliceMode)

    simple.Show()
    simple.Render()
    return {'scalarRange': self.scalarRange,
            'bounds': self.bounds,
            'extent': self.extent,
            'center': self.center,
            'sliceInfo': sliceInfo}


  @exportRpc("volumeRender")
  def volumeRender(self):
    global view
    self._extractVolumeImageData()
    (lenx, leny, lenz) = (self.bounds[1] - self.bounds[0],
                          self.bounds[3] - self.bounds[2],
                          self.bounds[5] - self.bounds[4])
    (midx, midy, midz) = (self.center[0], self.center[1], self.center[2])
    maxDim = max(lenx, leny, lenz)
    # Adjust camera properties appropriately
    view.CameraFocalPoint = self.center
    view.CenterOfRotation = self.center
    view.CameraPosition = [midx - self.DISTANCE_FACTOR * maxDim, midy, midz]
    view.CameraViewUp = [0, 0, 1]

    # Create RGB transfer function
    rgbPoints = [self.scalarRange[0], 0, 0, 0, self.scalarRange[1], 1, 1, 1]
    self.lookupTable = simple.GetLookupTableForArray(self.colorArrayName, 1)
    self.lookupTable.RGBPoints = rgbPoints
    self.lookupTable.ScalarRangeInitialized = 1.0
    self.lookupTable.ColorSpace = 'RGB'

    # Create opacity transfer function
    sofPoints = [self.scalarRange[0], 0, 0.5, 0,
                 self.scalarRange[1], 1, 0.5, 0]
    self.sof = simple.CreatePiecewiseFunction()
    self.sof.Points = sofPoints

    self.rep.ColorArrayName = self.colorArrayName
    self.rep.Representation = 'Volume'
    self.rep.ScalarOpacityFunction = self.sof
    self.rep.LookupTable = self.lookupTable
    simple.Show()
    simple.Render()
    return {'scalarRange': self.scalarRange,
            'bounds': self.bounds,
            'extent': self.extent,
            'sofPoints': sofPoints,
            'rgbPoints': rgbPoints}

  @exportRpc("updateSof")
  def updateSof(self, sofPoints):
    self.sof = simple.CreatePiecewiseFunction()
    self.sof.Points = sofPoints
    self.rep.ScalarOpacityFunction = self.sof
    simple.Render()

  @exportRpc("updateColorMap")
  def updateColorMap(self, rgbPoints):
    self.lookupTable = simple.GetLookupTableForArray(self.colorArrayName, 1)
    self.lookupTable.RGBPoints = rgbPoints

    self.rep.LookupTable = self.lookupTable

  @exportRpc("extractSubgrid")
  def extractSubgrid(self, bounds):
    if(self.subgrid is not None):
      simple.Delete(self.subgrid)
    simple.SetActiveSource(self.srcObj)
    self.subgrid = simple.ExtractSubset()
    self.subgrid.VOI = bounds
    simple.SetActiveSource(self.subgrid)

    self.rep = simple.Show()
    self.rep.ScalarOpacityFunction = self.sof
    self.rep.ColorArrayName = self.colorArrayName
    self.rep.Representation = 'Volume'
    self.rep.SelectionPointFieldDataArrayName = self.colorArrayName
    self.rep.LookupTable = self.lookupTable

    simple.Hide(self.srcObj)
    simple.SetActiveSource(self.subgrid)
    simple.Render()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Midas+ParaViewWeb application")
    server.add_arguments(parser)
    parser.add_argument("--data-dir", default=os.getcwd(),
        help="path to data directory", dest="path")
    parser.add_argument("--width", default=575,
        help="width of the render window", dest="width")
    parser.add_argument("--height", default=575,
        help="height of the render window", dest="height")
    args = parser.parse_args()

    dataPath = args.path
    authKey = args.authKey
    width = args.width
    height = args.height

    initView(width, height)
    server.start_webserver(options=args, protocol=MidasApp)
