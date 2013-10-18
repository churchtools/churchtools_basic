#!/bin/bash
#
# Copyright (c) 2013, Bo Maryniuk (bo@suse.de)
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without modification,
# are permitted provided that the following conditions are met:
#
# Redistributions of source code must retain the above copyright notice,
# this list of conditions and the following disclaimer. Redistributions in binary 
# form must reproduce the above copyright notice, this list of conditions and the
# following disclaimer in the documentation and/or other materials provided with
# the distribution.
#
# Neither the name of the SUSE Linux Products GmbH nor the names of its contributors
# may be used to endorse or promote products derived from this software without
# specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
# AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
# IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
# INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
# DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
# OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
# OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
# OF THE POSSIBILITY OF SUCH DAMAGE. 
#

#
# Setup.
#
TARGET="target-release"
COMPILE_JS="yes"
ARC=""

#
# Check environment
#
function check_environment() {
    JV="";
    if [ ! -z $(which java 2>/dev/null) ]; then
	JV=$(java -version 2>&1 | grep version | awk '{print $3}' | sed 's/"//g')
    fi

    if [ -z $JV ]; then
	    echo "Warning: Java was not found in the standard path. Skipping JavaScript compilation."
	COMPILE_JS="no"
    else
	echo "Java version: $JV"
    fi
}

#
# Refresh distro output.
#
function make_target() {
    if [ -d "$TARGET" ]; then
	rm -rf $TARGET
    fi
    mkdir -p $TARGET/churchtools-$1
    echo $TARGET/churchtools-$1
}

#
# Get the version of the release.
#
function get_version() {
    echo $(cat index.php | grep 'Release Version' | sed 's/.*:*\s//g')
}

#
# Copy all the sources to the release
#
function move_sources() {
    for obj in "index.php" "LICENSE" "sites" "system" "utils"; do
	cp -r $obj $1
    done
}


#
# Minimize JavaScript.
#
function compile_javascript() {
    UTILS_PATH=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd )
    for js in $(find target-release/churchtools-$1/system -name "c*.js"); do
	echo "Compiling $js"
	cp $js $js.source
	#java -jar $UTILS_PATH/js-compiler.jar --js $js.source > $js
	rm $js.source
    done
}


#
# Archiving the source for the packaging.
#
function archive_sources() {
    cd target-release;
    ARC=$(pwd)/churchtools-$1.tar.bz2
    tar cf - churchtools-$1 | bzip2 > $ARC
    rm -rf churchtools-$1
}


#
# Main
#
# Get to the right directory root
cd $(cd $(dirname "${BASH_SOURCE[0]}") && pwd )
cd ..

# Check environment and source version
check_environment;
VERSION=$(get_version);
if [ -z $VERSION ]; then
    echo 'Error: Please add "Release Version: N.NN" tag into index.php!'
    exit;
else
    echo "Preparing version $VERSION";
fi

# Prepare sources
move_sources $(make_target $VERSION);
if [ "$COMPILE_JS" = "yes" ]; then
    compile_javascript $VERSION;
fi

# Archive
archive_sources $VERSION;

# Finish
echo
echo "Done"
echo
echo "Source archive has been created: $ARC"
echo

