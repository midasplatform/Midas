#!/bin/sh
mkdir dist
javac -g  -d bin -verbose -source 5 -target 6 src/com/kitware/utils/*.java src/com/kitware/utils/exception/*.java -cp "[PATH_TO_JRE/jre/lib/plugin.jar;"
cd dist 
jar cvfm MidasUploader.jar ../src/manifest *.class com
jarsigner -keystore ../kitware!.keystore MidasUploader.jar mycert
cd ..
