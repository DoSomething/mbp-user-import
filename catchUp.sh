#!/bin/bash

cd data/AfterSchool
COUNTER=0
for i in $( ls ); do
  COUNTER=$((COUNTER+1))
  echo item: $COUNTER $i
  php ../../mbp-user-import.php nextFile AfterSchool
done
