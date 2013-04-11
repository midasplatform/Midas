# import to process args
import sys
import os

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
rep = None
imageData = None
center = [0, 0, 0]
bounds = None
scalarRange = None
srcObj = None

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
  @exportRpc("loadData")
  def loadData(self, properties):
    global dataPath, srcObj, rep
    mainpath = os.path.join(dataPath, "main")

    if os.path.isdir(mainpath):
      files = os.listdir(mainpath)
      for file in files:
        fullpath = os.path.join(mainpath, file)
        if os.path.isfile(fullpath):
          srcObj = simple.OpenDataFile(fullpath)
          simple.SetActiveSource(srcObj)
          rep = simple.Show()

          print 'Loaded %s into scene' % fullpath
    else:
      print 'Error: '+mainpath+' does not exist\n'
      raise Exception("The main directory does not exist")
    simple.ResetCamera()
    simple.Render()
    return srcObj

  @exportRpc("volumeRender")
  def volumeRender(self):
      global srcObj, center, scalarRange, bounds, rep
      if(srcObj.GetPointDataInformation().GetNumberOfArrays() == 0):
        print 'Error: no data information arrays'
        raise Exception('No data information arrays')

      imageData = srcObj.GetPointDataInformation().GetArray(0)
      scalarRange = imageData.GetRange()

      bounds = srcObj.GetDataInformation().DataInformation.GetBounds()
      (midx, midy, midz) = ((bounds[1] + bounds[0]) / 2.0,
                            (bounds[3] + bounds[2]) / 2.0,
                            (bounds[5] + bounds[4]) / 2.0)
      (lenx, leny, lenz) = (bounds[1] - bounds[0],
                            bounds[3] - bounds[2],
                            bounds[5] - bounds[4])
      maxDim = max(lenx, leny, lenz)
      center = [midx, midy, midz]
      # Adjust camera properties appropriately
      view.CameraFocalPoint = center
      view.CenterOfRotation = center
      view.CameraPosition = [midx - 1.6 * maxDim, midy, midz]
      view.CameraViewUp = [0, 0, 1]
  
      # Create RGB transfer function
      lookupTable = simple.GetLookupTableForArray(imageData.Name, 1)
      lookupTable.RGBPoints = [scalarRange[0], 0, 0, 0, scalarRange[1], 1, 1, 1]
      lookupTable.ScalarRangeInitialized = 1.0
      lookupTable.ColorSpace = 0  # 0 corresponds to RGB
  
      # Create opacity transfer function
      sof = simple.CreatePiecewiseFunction()
      sof.Points = [scalarRange[0], 0, 0.5, 0,
                    scalarRange[1], 1, 0.5, 0]
  
      rep.ColorArrayName = imageData.Name
      rep.Representation = 'Volume'
      # TODO don't use texture mapping only unless we have GPU issues...
      rep.VolumeRenderingMode = 'Texture Mapping Only'
      rep.ScalarOpacityFunction = sof
      rep.LookupTable = lookupTable
      simple.Render()
      return (scalarRange, bounds)

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
