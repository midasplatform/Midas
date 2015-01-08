#!/bin/sh
mkdir bin
mkdir dist
javac -g  -d bin -verbose -source 5 -target 6 src/com/kitware/utils/*.java src/com/kitware/utils/exception/*.java src/org/json/*.java -cp "[PATH_TO_JRE/jre/lib/plugin.jar;"
cd bin
jar cvfm ../dist/MidasUploader.jar ../src/manifest com org
cd ../dist
jarsigner -keystore ../kitware!.keystore MidasUploader.jar mycert
cd ..
