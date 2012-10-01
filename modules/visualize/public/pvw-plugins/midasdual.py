import pwsimple
import math

# Midas volume rendering ParaviewWeb plugin

# Initialize the volume rendering state
def InitViewState (cameraFocalPoint, cameraPosition, colorArrayName, colorMap, sliceVal, sliceMode, parallelScale, cameraUp):
  if type(colorArrayName) is unicode:
    colorArrayName = colorArrayName.encode('ascii', 'ignore')
  
  if type(sliceMode) is unicode:
    sliceMode = sliceMode.encode('ascii', 'ignore')
  
  activeView = pwsimple.CreateIfNeededRenderView()
  
  activeView.CameraFocalPoint = cameraFocalPoint
  activeView.CameraPosition = cameraPosition
  activeView.CameraViewUp = cameraUp
  activeView.CameraParallelProjection = True
  activeView.CameraParallelScale = parallelScale
  activeView.CenterOfRotation = activeView.CameraFocalPoint
  activeView.CenterAxesVisibility = False
  activeView.OrientationAxesVisibility = False
  activeView.Background = [0.0, 0.0, 0.0]
  activeView.Background2 = [0.0, 0.0, 0.0]
  
  lookupTable = pwsimple.GetLookupTableForArray(colorArrayName, 1)
  lookupTable.RGBPoints = colorMap
  lookupTable.ScalarRangeInitialized = 1.0
  lookupTable.ColorSpace = 0  # 0 corresponds to RGB
  
  dataRep = pwsimple.GetDisplayProperties()
  dataRep.Representation = 'Slice'
  dataRep.SliceMode = sliceMode
  dataRep.Slice = sliceVal
  dataRep.LookupTable = lookupTable
  dataRep.ColorArrayName = colorArrayName
  
  retVal = {}
  retVal['lookupTable'] = lookupTable
  retVal['activeView'] = activeView
  return retVal

# Change the slice value and run slice filter on meshes
def ChangeSlice (volume, slice, sliceMode):
  if type(sliceMode) is unicode:
    sliceMode = sliceMode.encode('ascii', 'ignore')
  
  dataRep = pwsimple.GetDisplayProperties(volume)
  dataRep.Slice = slice

# Change the slice mode (what plane we are slicing in)
def ChangeSliceMode (volume, slice, sliceMode, parallelScale, cameraPosition, cameraUp):
  if type(sliceMode) is unicode:
    sliceMode = sliceMode.encode('ascii', 'ignore')
  
  activeView = pwsimple.GetActiveView()
  activeView.CameraPosition = cameraPosition
  activeView.CameraViewUp = cameraUp
  activeView.CameraParallelScale = parallelScale
  
  dataRep = pwsimple.GetDisplayProperties(volume)
  dataRep.SliceMode = sliceMode
  return ChangeSlice(volume, slice, sliceMode)

# Place a sphere surface into the scene
def ShowSphere (point, color, radius, objectToDelete, input):
  if objectToDelete is not False:
    pwsimple.SetActiveSource(objectToDelete)
    pwsimple.Delete()
  
  glyph = pwsimple.Sphere()
  glyph.Radius = 2
  glyph.Center = point
  dataRep = pwsimple.Show()
  dataRep.Representation = 'Surface'
  dataRep.DiffuseColor = color
  
  pwsimple.SetActiveSource(input);
  
  retVal = {}
  retVal['glyph'] = glyph
  return retVal

def ChangeWindow (rgbPoints, colorArrayName):
  if type(colorArrayName) is unicode:
    colorArrayName = colorArrayName.encode('ascii', 'ignore')
  
  lookupTable = pwsimple.GetLookupTableForArray(colorArrayName, 1)
  lookupTable.RGBPoints = rgbPoints
  
  retVal = {}
  retVal['lookupTable'] = lookupTable
  return retVal

