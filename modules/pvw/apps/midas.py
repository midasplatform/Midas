# import to process args
import sys
import os
import math

try:
    import argparse
except ImportError:
    # since  Python 2.6 and earlier don't have argparse, we simply provide
    # the source for the same as _argparse and we use it instead.
    import _argparse as argparse

# import annotations
from autobahn.wamp import exportRpc

# import paraview modules.
from paraview import simple, web, servermanager, web_helper

# Setup global variables
timesteps = []
currentTimeIndex = 0
timekeeper = servermanager.ProxyManager().GetProxy("timekeeper", "TimeKeeper")
pipeline = web_helper.Pipeline('Kitware')
lutManager = web_helper.LookupTableManager()
view = None
dataPath = None

# Initialize the render window
def initView(width, height):
  global view, lutManager

  view = simple.GetRenderView()
  simple.Render()
  view.ViewSize = [width, height]
  lutManager.setView(view)
  print 'View created successfully (%dx%d)' % (width, height)

# This class defines the exposed RPC methods for the midas application
class MidasApp(web.ParaViewServerProtocol):
  DISTANCE_FACTOR = 1.6
  colorArrayName = None
  sof = None
  lookupTable = None
  srcObj = None
  bounds = None
  center = None
  rep = None
  scalarRange = None
  imageData = None
  subgrid = None
  sliceMode = None

  def __extractVolumeImageData(self):
    if(self.srcObj.GetPointDataInformation().GetNumberOfArrays() == 0):
      print 'Error: no data information arrays'
      raise Exception('No data information arrays')

    self.imageData = self.srcObj.GetPointDataInformation().GetArray(0)
    self.colorArrayName = self.imageData.Name
    self.scalarRange = self.imageData.GetRange()
    self.bounds = self.srcObj.GetDataInformation().DataInformation.GetBounds()
    self.center = [(self.bounds[1] + self.bounds[0]) / 2.0,
                   (self.bounds[3] + self.bounds[2]) / 2.0,
                   (self.bounds[5] + self.bounds[4]) / 2.0]

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
    simple.ResetCamera()
    simple.Render()
    return self.srcObj


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
      sliceNum = int(math.floor(self.center[2]))
      cameraParallelScale = max(self.bounds[1] - self.bounds[0],
                                self.bounds[3] - self.bounds[2]) / 2.0
      cameraPosition = [self.center[0], self.center[1], self.bounds[4] - 10]
      maxSlices = self.bounds[5] - self.bounds[4]
      cameraViewUp = [0, -1, 0]
    elif(sliceMode == 'XZ Plane'):
      sliceNum = int(math.floor(self.center[1]))
      cameraParallelScale = max(self.bounds[1] - self.bounds[0],
                                self.bounds[5] - self.bounds[4]) / 2.0
      maxSlices = self.bounds[3] - self.bounds[2]
      cameraPosition = [self.center[0], self.bounds[3] + 10, self.center[2]]
      cameraViewUp = [0, 0, 1]
    elif(sliceMode == 'YZ Plane'):
      sliceNum = int(math.floor(self.center[0]))
      cameraParallelScale = max(self.bounds[3] - self.bounds[2],
                                self.bounds[5] - self.bounds[4]) / 2.0
      maxSlices = self.bounds[1] - self.bounds[0]
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

    self.sliceMode = sliceMode
    return {'slice': sliceNum,
            'maxSlices': maxSlices}


  @exportRpc("sliceRender")
  def sliceRender(self, sliceMode):
    global view
    self.__extractVolumeImageData()
    (midx, midy, midz) = (self.center[0], self.center[1], self.center[2])
    (lenx, leny, lenz) = (self.bounds[1] - self.bounds[0],
                          self.bounds[3] - self.bounds[2],
                          self.bounds[5] - self.bounds[4])
    maxDim = max(lenx, leny, lenz)
    # Adjust camera properties appropriately
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
    self.lookupTable.ColorSpace = 0  # 0 corresponds to RGB

    self.rep.ColorArrayName = self.colorArrayName
    self.rep.Representation = 'Slice'
    self.rep.LookupTable = self.lookupTable

    sliceInfo = self.setSliceMode(sliceMode)

    simple.Show()
    simple.Render()
    return {'scalarRange': self.scalarRange,
            'bounds': self.bounds,
            'rgbPoints': rgbPoints,
            'sliceInfo': sliceInfo}


  @exportRpc("volumeRender")
  def volumeRender(self):
    global view
    self.__extractVolumeImageData()
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
    self.lookupTable.ColorSpace = 0  # 0 corresponds to RGB

    # Create opacity transfer function
    sofPoints = [self.scalarRange[0], 0, 0.5, 0,
                 self.scalarRange[1], 1, 0.5, 0]
    self.sof = simple.CreatePiecewiseFunction()
    self.sof.Points = sofPoints

    self.rep.ColorArrayName = self.colorArrayName
    self.rep.Representation = 'Volume'
    # TODO don't use texture mapping only unless we have GPU issues...
    self.rep.VolumeRenderingMode = 'Texture Mapping Only'
    self.rep.ScalarOpacityFunction = self.sof
    self.rep.LookupTable = self.lookupTable
    simple.Show()
    simple.Render()
    return {'scalarRange': self.scalarRange,
            'bounds': self.bounds,
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
    self.rep.VolumeRenderingMode = 'Texture Mapping Only'
    self.rep.SelectionPointFieldDataArrayName = self.colorArrayName
    self.rep.LookupTable = self.lookupTable

    simple.Hide(self.srcObj)
    simple.SetActiveSource(self.subgrid)
    simple.Render()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Midas+ParaViewWeb application")
    web.add_arguments(parser)
    parser.add_argument("--data-dir", default=os.getcwd(),
        help="path to data directory", dest="path")
    parser.add_argument("--width", default=575,
        help="width of the render window", dest="width")
    parser.add_argument("--height", default=575,
        help="height of the render window", dest="height")
    args = parser.parse_args()

    dataPath = args.path
    initView(width=args.width, height=args.height)
    web.start_webserver(options=args, protocol=MidasApp)
