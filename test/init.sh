# Copyright (c) 2017-present trivago GmbH
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

NAME=application
export SYMFONY_ENV=test
cd $(dirname "${BASH_SOURCE[0]}")
HAS_SQLITE=$(php -r "echo extension_loaded('pdo_sqlite') ? 1 : 0;")
if [ "${HAS_SQLITE}" -eq "0" ]; then
   echo "Sorry for this example we need pdo_sqlite extension!";
   exit;
fi

echo "Downloading symfony binary"
curl -LsS https://symfony.com/installer -o ./symfony
chmod a+x ./symfony
echo "Creating a new symfony project"
rm -rf ${NAME}
./symfony new ${NAME} 3.4
rm ./symfony
cd ${NAME}
echo "Installing the jade library"
curl -LsS https://getcomposer.org/composer.phar -o ./composer
chmod a+x ./composer
./composer require trivago/jade
./composer require webmozart/assert
./composer require "codeception/codeception" --dev
echo "Symlinking jade to the parent repository."
rm -rf vendor/trivago/jade
ln -s ../../../.. vendor/trivago/jade
rm ./composer
echo "Changing the necessary files"
cp -r ../files/app .
rm -rf src tests app/config/jade.yml
ln -s ../../../files/app/config/jade.yml app/config/jade.yml
ln -s ../files/src src
ln -s ../files/tests tests
ln -s ../files/codeception.yml codeception.yml
echo "Setting up the database"
mkdir var/data
bin/console doctrine:schema:update --force
