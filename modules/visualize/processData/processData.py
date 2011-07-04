#!/usr/bin/pvpython
 
import sys
import os
import json
from paraview.simple import *

file = sys.argv[1]
savePath = sys.argv[2]

if(os.path.exists(savePath+"/screenshot1.png")):
  os.remove(savePath+"/screenshot1.png")
if(os.path.exists(savePath+"/screenshot2.png")):
  os.remove(savePath+"/screenshot2.png")
if(os.path.exists(savePath+"/screenshot3.png")):
  os.remove(savePath+"/screenshot3.png")
if(os.path.exists(savePath+"/screenshot4.png")):
  os.remove(savePath+"/screenshot4.png")
if(os.path.exists(savePath+"/metadata.txt")):
  os.remove(savePath+"/metadata.txt")

split = os.path.splitext( file )
print file
if(split[1] == '.pvsm'):
  reader = servermanager.LoadState(file)
  SetActiveView(GetRenderView())
  view = GetRenderViews()[0]
  view.ResetCamera()
  view.StillRender()
  sources = GetSources()
  keys = sources.keys()
  print sources.keys()
  source = sources[keys[0]]
else:
  reader = OpenDataFile(file)
  Show(reader)
  view = Render()
  source = reader

## render png
view.ResetCamera()
view.UseTexturedBackground = 1
view.BackgroundTexture = []
view.Background = [0.0, 0.0, 0.0]
view.Background2 = [1, 1, 1]

view.WriteImage(savePath+"/screenshot1.png", "vtkPNGWriter", 1)
camera = view.GetActiveCamera()
camera.Azimuth(90)

#draw the object
view.WriteImage(savePath+"/screenshot2.png", "vtkPNGWriter", 1)
camera.Azimuth(90)

#draw the object
view.WriteImage(savePath+"/screenshot3.png", "vtkPNGWriter", 1)
camera.Azimuth(90)

#draw the object
view.WriteImage(savePath+"/screenshot4.png", "vtkPNGWriter", 1)

info = source.GetDataInformation()
jsonInfo = json.dumps([info.GetPrettyDataTypeString (), info.GetNumberOfPoints(), info.GetNumberOfCells(), info.GetPolygonCount(), info.GetBounds()] )

f = open(savePath+'/metadata.txt', 'w')
f.write(jsonInfo)