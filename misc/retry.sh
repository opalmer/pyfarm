# USAGE: 
#    $ source retry.sh
#    $ retry <command>

function retry {
    local attempt=1
    local command="${@}" 

    while [ $attempt -ne 10 ]; do
        echo "(run $attempt/10) running $command"
        $command
        if [[ $? -eq 0 ]]; then
            echo "(success) $command"
            break
        else
            ((attempt++))
            echo "(FAILED) $command"
            sleep 3
        fi
    done
}

