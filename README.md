Sound Transit Beta Site Crawler

SETUP:
Install composer dependencies
    php composer.phar install

RUN:
Start Selenium Server
    java -jar selenium-server-standalone-3.14.0.jar
Run the feature file
    bin/behat
