import pwsimple

# Midas volume rendering ParaviewWeb plugin

# Read the data from the source file, show the object, and return the data information
def OpenData (filename, otherMeshes):
  if type(filename) is unicode:
    filename = filename.encode('ascii', 'ignore')
  
  srcObj = pwsimple.OpenDataFile(filename)
  pwsimple.Show()
  imageData = pwsimple.GetDataInformation()
  
  # Load other meshes into scene
  meshes = []
  for mesh in otherMeshes:
    source = pwsimple.OpenDataFile(mesh['path'])
    pwsimple.SetActiveSource(source)
    properties = pwsimple.Show()
    properties.Representation = 'Surface'
    properties.DiffuseColor = [float(mesh['diffuseColor'][0]), float(mesh['diffuseColor'][1]), float(mesh['diffuseColor'][2])]
    properties.Orientation = [float(mesh['orientation'][0]), float(mesh['orientation'][1]), float(mesh['orientation'][2])]
    meshes.append({'source' : source, 'item': mesh['item'], 'visible': True})
  
  pwsimple.SetActiveSource(srcObj)
  
  retVal = {}
  retVal['input'] = srcObj
  retVal['imageData'] = imageData
  retVal['meshes'] = meshes
  return retVal

# Initialize the volume rendering state
def InitViewState (cameraFocalPoint, cameraPosition, colorArrayName, colorMap, sofPoints):
  if type(colorArrayName) is unicode:
    colorArrayName = colorArrayName.encode('ascii', 'ignore')
  
  activeView = pwsimple.CreateIfNeededRenderView()
  
  activeView.CameraFocalPoint = cameraFocalPoint
  activeView.CameraPosition = cameraPosition
  activeView.CameraViewUp = [0.0, 0.0, 1.0]
  activeView.CameraParallelProjection = False
  activeView.CenterOfRotation = activeView.CameraFocalPoint
  activeView.Background = [0.0, 0.0, 0.0]
  activeView.Background2 = [0.0, 0.0, 0.0]
  
  lookupTable = pwsimple.GetLookupTableForArray(colorArrayName, 1)
  lookupTable.RGBPoints = colorMap
  lookupTable.ScalarRangeInitialized = 1.0
  lookupTable.ColorSpace = 0  # 0 corresponds to RGB
  
  # Initial scalar opacity function
  sof = pwsimple.CreatePiecewiseFunction()
  sof.Points = sofPoints
  
  dataRep = pwsimple.Show()
  dataRep.ScalarOpacityFunction = sof
  dataRep.Representation = 'Volume'
  dataRep.ColorArrayName = colorArrayName
  dataRep.LookupTable = lookupTable
  
  retVal = {}
  retVal['sof'] = sof
  retVal['lookupTable'] = lookupTable
  retVal['activeView'] = activeView
  return retVal

# Extract a subgrid of the source
def ExtractSubgrid (source, bounds, lookupTable, sof, colorArrayName, toHide):
  pwsimple.SetActiveSource(source)
  subgrid = pwsimple.ExtractSubset()
  subgrid.VOI = bounds
  pwsimple.SetActiveSource(subgrid)
  
  if type(colorArrayName) is unicode:
    colorArrayName = colorArrayName.encode('ascii', 'ignore')
  
  dataRep = pwsimple.Show()
  dataRep.ScalarOpacityFunction = sof
  dataRep.Representation = 'Volume'
  dataRep.SelectionPointFieldDataArrayName = colorArrayName
  dataRep.ColorArrayName = colorArrayName
  dataRep.LookupTable = lookupTable
  
  pwsimple.SetActiveSource(source)
  pwsimple.Hide(source)
  if toHide:
    pwsimple.Hide(toHide)
  
  pwsimple.SetActiveSource(subgrid)
  return subgrid

# Set the camera position and camera view up vectors
def SetCamera (cameraPosition, cameraViewUp):
  activeView = pwsimple.GetActiveView()
  activeView.CameraPosition = cameraPosition
  activeView.CameraViewUp = cameraViewUp

# Update the scalar color mapping
def UpdateColorMap (colorMap, colorArrayName):
  if type(colorArrayName) is unicode:
    colorArrayName = colorArrayName.encode('ascii', 'ignore')
  
  lookupTable = pwsimple.GetLookupTableForArray(colorArrayName, 1)
  lookupTable.RGBPoints = colorMap
  
  dataRep = pwsimple.Show()
  dataRep.LookupTable = lookupTable

