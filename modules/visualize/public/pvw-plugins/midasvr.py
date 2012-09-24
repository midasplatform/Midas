import pwsimple

# Midas volume rendering ParaviewWeb plugin

# Read the data from the source file, show the object, and return the data information
def OpenData (filename):
  if type(filename) is unicode:
    filename = filename.encode('ascii', 'ignore')
  
  srcObj = pwsimple.OpenDataFile(filename)
  pwsimple.Show()
  imageData = pwsimple.GetDataInformation()
  retVal = {}
  retVal['input'] = srcObj
  retVal['imageData'] = imageData
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

