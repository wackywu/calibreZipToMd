#!/bin/bash

zipFile=""
resultFile=""
rootPath=$(cd `dirname $0`; pwd)
tempPath="$rootPath/temp"

function getZipFileName() {
  local curTime
  local timeStamp
  curTime=`date "+%Y-%m-%d %H:%M:%S"`
  timeStamp=`date -d "$current" +%s`
  timeStamp=$((timeStamp*1000+`date "+%N"`/1000000))
  curTime=`date "+%Y%m"`
  zipFile="$tempPath/$curTime$timeStamp.zip"
}

function convertZipFile() {
  local exitcode
  local output

  output=`ebook-convert $1 $zipFile`
  exitcode=$?
  if [ $exitcode == 0 ]
  then
    resultFile=`php $rootPath/Main.php $zipFile`
    exitcode=$?
    rm -rf $zipFile
  fi
  return $exitcode
}

mkdir -p $tempPath
getZipFileName
convertZipFile $1
if [ $? == 0 ] 
then
  echo $resultFile
else
  echo $?
fi
exit $?



