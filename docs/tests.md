Tests
=======
Jade comes with both unit tests (using phpunit) and functional tests (using codeception).
Each of them covers a part of the library but together they are covering big part of Jade.
The codeception Cept file needs a little bit of clean up and more separation so when a test fails the test continues.

Right now the most important test is the codeception test to cover all the functionality of Jade.

Currently the test runs using sqlite which means you need sqlite_pdo extension.
To run the tests first you have to initialize the framework:
`bash test/init.sh`

You only need to run the init script once.


Then run the tests
`bash test/run_tests.sh`


Parts that are not covered by codeception
--------
* Pagination
* Max relationship depth
* Security
* Inheritance
* Virtual paths
* Virtual properties
* Excluded attributes

All these sections are being used in internal projects of trivago which means they are safe to use but not should not be changed until they have coverage.
