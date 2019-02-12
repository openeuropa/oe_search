@api
Feature: Search block
  In order to be able to showcase Search
  As an anonymous
  I want to search on ec.europa.eu by submitting search block form

  Scenario: I could be redirected to ec.europa.eu search page on try to send search request in search block
    Given the following languages are available:
      | languages |
      | en        |
      | fr        |
    And I am on "the English home page"
    When I fill in "Search" with "European Commission"
    And I press "Search"
    Then I should be redirected to "https://ec.europa.eu/search/?QueryText=European%20Commission&swlang=en"
  
    When I am on "the French home page"
    When I fill in "Search" with "European Commission"
    And I press "Search"
    Then I should be redirected to "https://ec.europa.eu/search/?QueryText=European%20Commission&swlang=fr"
