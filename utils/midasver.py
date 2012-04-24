"""
Simple script for versioning the documentation stored on the midaswiki.
Essentially, this is only useful as an example.
"""
import getpass
import mwdoc
import sys

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print "Please enter a source and destination version"
        sys.exit(-1)
    sourceVersion = sys.argv[1]
    destinationVersion = sys.argv[2]
    doc = mwdoc.Documentation('www.kitware.com', '/midaswiki/')
    print "This application will version the Midas Wiki documentation from ",
    print "%s to %s." % (sourceVersion, destinationVersion)
    username = raw_input("Username: ")
    password = getpass.getpass("Password: ")
    doc.login(username, password)
    prefixes = ['Documentation','Template:Documentation']
    doc.versionPages(sourceVersion, destinationVersion, prefixes)
