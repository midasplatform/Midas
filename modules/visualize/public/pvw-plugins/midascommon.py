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
    diffuseColor = [float(mesh['diffuseColor'][0]), float(mesh['diffuseColor'][1]), float(mesh['diffuseColor'][2])]
    orientation = [float(mesh['orientation'][0]), float(mesh['orientation'][1]), float(mesh['orientation'][2])]
    source = pwsimple.OpenDataFile(mesh['path'])
    pwsimple.SetActiveSource(source)
    properties = pwsimple.Show()
    properties.Representation = 'Surface'
    properties.DiffuseColor = diffuseColor
    properties.Orientation = orientation
    meshes.append({'source': source, 'item': mesh['item'], 'visible': True, 'diffuseColor': diffuseColor, 'orientation': orientation})
  
  pwsimple.SetActiveSource(srcObj)
  
  retVal = {}
  retVal['input'] = srcObj
  retVal['imageData'] = imageData
  retVal['meshes'] = meshes
  return retVal

# Set the camera position and camera view up vectors
def SetCamera (cameraPosition, cameraViewUp):
  activeView = pwsimple.GetActiveView()
  activeView.CameraPosition = cameraPosition
  activeView.CameraViewUp = cameraViewUp

# Set the camera position, focal point, and pscale
def MoveCamera (cameraPosition, cameraFocalPoint, cameraParallelScale):
  activeView = pwsimple.GetActiveView()
  activeView.CameraPosition = cameraPosition
  activeView.CameraFocalPoint = cameraFocalPoint
  activeView.CameraParallelScale = cameraParallelScale

# Update the scalar color mapping
def UpdateColorMap (colorMap, colorArrayName):
  if type(colorArrayName) is unicode:
    colorArrayName = colorArrayName.encode('ascii', 'ignore')
  
  lookupTable = pwsimple.GetLookupTableForArray(colorArrayName, 1)
  lookupTable.RGBPoints = colorMap
  
  dataRep = pwsimple.Show()
  dataRep.LookupTable = lookupTable

# Delete the source from the scene
def DeleteSource (source):
  pwsimple.Delete(source)

