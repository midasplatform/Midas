import pwsimple
import math

# Midas volume rendering ParaviewWeb plugin

# Initialize the volume rendering state
def InitViewState (cameraFocalPoint, cameraPosition, colorArrayName, colorMap, sliceVal, sliceMode, parallelScale, cameraUp, meshes, lineWidth):
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
  
  volume = pwsimple.GetActiveSource()
  meshSlices = []
  for mesh in meshes:
    pwsimple.SetActiveSource(mesh['source'])
    pwsimple.Hide()
    meshSlice = pwsimple.Slice(SliceType='Plane')
    
    meshSlice.SliceOffsetValues = [0.0]
    meshSlice.SliceType = 'Plane'
    meshSlice.SliceType.Origin = [0.0, 0.0, math.cos(math.radians(mesh['orientation'][2]))*sliceVal]
    meshSlice.SliceType.Normal = [0.0, 0.0, 1.0]
    meshDataRep = pwsimple.Show(meshSlice)
    meshDataRep.Representation = 'Points'
    meshDataRep.LineWidth = lineWidth
    meshDataRep.PointSize = lineWidth
    meshDataRep.AmbientColor = mesh['diffuseColor']
    meshDataRep.Orientation = mesh['orientation']
    meshSlices.append(meshSlice)
  
  pwsimple.SetActiveSource(volume)
  
  retVal = {}
  retVal['lookupTable'] = lookupTable
  retVal['activeView'] = activeView
  retVal['meshSlices'] = meshSlices
  return retVal

# Change the slice value and run slice filter on meshes
def ChangeSlice (volume, slice, sliceMode, meshes, lineWidth):
  if type(sliceMode) is unicode:
    sliceMode = sliceMode.encode('ascii', 'ignore')
  
  dataRep = pwsimple.GetDisplayProperties(volume)
  dataRep.Slice = slice
  
  # Delete any previous slice objects in the scene
  sources = pwsimple.GetSources()
  for key, srcId in sources:
    if key[:5] == 'Slice':
      pwsimple.Delete(sources[(key, srcId)])
  
  meshSlices = []
  for mesh in meshes:
    if sliceMode == 'XY Plane':
      origin = [0.0, 0.0, math.cos(math.radians(mesh['orientation'][2]))*slice]
      normal = [0.0, 0.0, 1.0]
    elif sliceMode == 'XZ Plane':
      origin = [0.0, math.cos(math.radians(mesh['orientation'][1]))*slice, 0.0]
      normal = [0.0, 1.0, 0.0]
    else:
      origin = [math.cos(math.radians(mesh['orientation'][0]))*slice, 0.0, 0.0]
      normal = [1.0, 0.0, 0.0]
    
    pwsimple.Hide(mesh['source'])
    meshSlice = pwsimple.Slice(Input = mesh['source'], SliceType='Plane')
    pwsimple.SetActiveSource(volume)
    
    meshSlice.SliceOffsetValues = [0.0]
    meshSlice.SliceType = 'Plane'
    meshSlice.SliceType.Origin = origin
    meshSlice.SliceType.Normal = normal
    meshDataRep = pwsimple.Show(meshSlice)
    
    meshDataRep.Representation = 'Points'
    meshDataRep.LineWidth = lineWidth
    meshDataRep.PointSize = lineWidth
    meshDataRep.AmbientColor = mesh['diffuseColor']
    meshDataRep.Orientation = mesh['orientation']
    meshSlices.append(meshSlice)
  
  pwsimple.SetActiveSource(volume)
  retVal = {}
  retVal['meshSlices'] = meshSlices
  retVal['sliceMode'] = sliceMode
  return retVal

# Change the slice mode (what plane we are slicing in)
def ChangeSliceMode (volume, slice, sliceMode, meshes, lineWidth, parallelScale, cameraPosition, cameraUp):
  if type(sliceMode) is unicode:
    sliceMode = sliceMode.encode('ascii', 'ignore')
  
  activeView = pwsimple.GetActiveView()
  activeView.CameraPosition = cameraPosition
  activeView.CameraViewUp = cameraUp
  activeView.CameraParallelScale = parallelScale
  
  dataRep = pwsimple.GetDisplayProperties(volume)
  dataRep.SliceMode = sliceMode
  return ChangeSlice(volume, slice, sliceMode, meshes, lineWidth)

# Place a sphere surface into the scene
def ShowSphere (point, color, radius, objectToDelete, input):
  if objectToDelete is not False:
    pwsimple.SetActiveSource(objectToDelete)
    pwsimple.Delete()
  
  glyph = pwsimple.Sphere()
  glyph.Radius = radius
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

