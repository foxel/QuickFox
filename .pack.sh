#!/bin/bash

rm -f pack.tgz

tar -czf pack.tgz --exclude=setup.php `ls`


