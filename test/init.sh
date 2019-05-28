# Copyright (c) 2017 trivago
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# @author Moein Akbarof <moein.akbarof@trivago.com>
# @date 2017-09-10

NAME=application
export SYMFONY_ENV=test
export COMPOSER_ROOT=$PWD
export GIT_BRANCH=`git name-rev --name-only HEAD`
cd $(dirname "${BASH_SOURCE[0]}")
HAS_SQLITE=$(php -r "echo extension_loaded('pdo_sqlite') ? 1 : 0;")
if [ "${HAS_SQLITE}" -eq "0" ]; then
   echo "Sorry for this example we need pdo_sqlite extension!";
   exit;
fi

echo "Downloading composer binary"
curl -LsS https://getcomposer.org/composer.phar -o ./composer
chmod a+x ./composer
echo "Creating a new symfony project"
rm -rf ${NAME}
./composer create-project symfony/website-skeleton ${NAME}
cd ${NAME}

echo "Installing test requirements"
export DEV_REQ=$(php -r '$json = file_get_contents("../../composer.json");
    $dev = json_decode($json,true)["require-dev"];
    $reqs = array_map(function($k,$v){return $k.":".$v;}, array_keys($dev), $dev);
    echo implode(" ", $reqs);')
../composer require -n --dev ${DEV_REQ}

echo "Installing the jade library"
../composer config repositories.jade vcs ${COMPOSER_ROOT}
../composer require trivago/jade:dev-${GIT_BRANCH}
echo "Symlinking jade to the parent repository."
rm -rf vendor/trivago/jade
ln -s ${COMPOSER_ROOT} vendor/trivago/jade
rm ../composer
echo "Changing the necessary files"
rm -rf src tests config codeception.yml
cp -r ../files/* .
cp -r ../files/.env .
# ln -s ../files/config/ config
# ln -s ../files/src src
# ln -s ../files/tests tests
# ln -s ../files/codeception.yml codeception.yml
echo "Setting up the database"
mkdir var/data
bin/console doctrine:schema:update --force
