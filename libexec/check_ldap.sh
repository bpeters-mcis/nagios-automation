#!/bin/bash

# Make sure we can connect to the destination LDAP.  We can just use prod, it's not a biggie if test goes wonky.
ldapsearch -x -h ldap.emich.edu -b 'ou=people,o=campus' -p 389 -s base > /dev/null
if [ $? = 0 ]; then
    CONNECT="Yes"
    echo $(date +%T) " - Destination LDAP online and available."
    ExitCode=0
else
    CONNECT="No"
    FAILURE="YES"
    echo $(date +%T) " - (FAILURE!) LDAP Failed"
    ExitCode=2
fi

exit $ExitCode
