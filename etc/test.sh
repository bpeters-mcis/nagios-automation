if cmp -s "objects/servers_from_lansweeper_new.cfg" "objects/servers_from_lansweeper.cfg" ; then
    #rm objects/servers_from_lansweeper.cfg
    #mv objects/servers_from_lansweeper_new.cfg objects/servers_from_lansweeper.cfg
    #RESTART="Yes"
    echo "Server definitions are different."
else
    echo "Same."
	#rm objects/servers_from_lansweeper_new.cfg
fi
