@javascript
Feature: Home Page

Scenario: Verify Global Search
    Given I am on "/"
    When I click the ".svg-search" element
    And wait for the ".global-search-con" element to be visible

Scenario: Verify no page not found errors
    Given I am on "/"
    And I visit each link to verify no page not found errors

Scenario: Verify valid urls
    Given I go to "/" and visit each link to verify there are no 404s