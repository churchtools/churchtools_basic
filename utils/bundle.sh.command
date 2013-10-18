datename=`date '+%Y%m%d'`
rm -rf ../$datename
mkdir $datename
dirname=$datename/churchtools
srcdirname=$datename/churchtools_src

echo $dirname
mkdir $dirname

cp *.php $dirname
cp -r system $dirname
mkdir $dirname/docs
cp docs/*.pdf $dirname/docs
cp docs/*.txt $dirname/docs

mkdir $dirname/sites
mkdir $dirname/sites/default
mkdir $dirname/sites/default/fotos
cp sites/default/churchtools.standard.config $dirname/sites/default
cp sites/default/fotos/* $dirname/sites/default/fotos/
export LC_ALL=C

fnDoProcess()
{
	find . -name "c*.js"|while read RECORD
	do
	  echo $RECORD "$RECORD"_orig
	  mv $RECORD "$RECORD"_orig
	  java -jar ../../../../js-compiler.jar --js="$RECORD"_orig --js_output_file=$RECORD
	  rm "$RECORD"_orig
	  # you could also manipulate the record here or run other commands
	done
}


mkdir $srcdirname
cp -r system $srcdirname

cd $dirname/system/churchcore
fnDoProcess

cd ../churchdb
fnDoProcess

cd ../churchresource
fnDoProcess

cd ../churchservice
fnDoProcess

cd ../churchcal
fnDoProcess

cd ../churchwiki
fnDoProcess

cd ../main
fnDoProcess

cd ../../..
zip -rT churchtools_"$datename".zip churchtools

cd ..
mv $datename ..
echo ready
sleep 3
