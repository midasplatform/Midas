import pwsimple

# Midas volume rendering ParaviewWeb plugin

# Initialize the volume rendering state
def InitViewState ():
  activeView = pwsimple.CreateIfNeededRenderView()
  pwsimple.ResetCamera()
  
  activeView.CenterOfRotation = activeView.CameraFocalPoint
  
  retVal = {}
  retVal['activeView'] = activeView
  return retVal

def ToggleEdges (input):
  props = pwsimple.GetDisplayProperties(input)
  if props.Representation == 'Surface':
    props.Representation = 'Surface With Edges'
  elif props.Representation == 'Surface With Edges':
    props.Representation = 'Surface'

def ResetCamera ():
  pwsimple.ResetCamera()

