"""
This script simply takes a list of issues that have been dumped from Mantis
via CSV and formats them into a wiki table. It takes the three headers of the
CSV and makes them the three headers of the wiki table. I use this to create
a changelog for the Midas Wiki.
"""
import sys
import csv

if __name__ == '__main__':
    reader = csv.reader(open(sys.argv[1], 'rb'), delimiter=',')
    writer = open(sys.argv[2],'wb')
    count = 0
    for row in reader:
        if count == 0:
            newRow = "{| border=1\n!%s\n!%s\n!%s\n" % (row[0], row[1], row[2])
        else:
            newRow = "|----\n|[http://public.kitware.com/MidasBT/view.php?id=%s %s]\n|%s\n|%s\n" % (row[0], row[0], row[1], row[2])
        writer.write(newRow)
        count +=1
    writer.write("|----\n|}\n")
    writer.close()
