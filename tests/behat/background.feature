@local @local_bgbling
Feature: Show a background video on nominated pages
  In order to make the site look more inviting
  As an administrator
  I need to show a background video on the pages I choose

  Background:
    Given the following config values are set as admin:
      | areas    | login                      | local_bgbling |
      | source   | url                        | local_bgbling |
      | videourl | https://vimeo.com/76979871 | local_bgbling |

  Scenario: The background layer appears on the login page when enabled
    Given the following config values are set as admin:
      | enabled | 1 | local_bgbling |
    When I visit "/login/index.php"
    Then ".local-bgbling" "css_element" should exist
    And "body.local-bgbling-active" "css_element" should exist

  Scenario: The background layer is not shown when disabled
    Given the following config values are set as admin:
      | enabled | 0 | local_bgbling |
    When I visit "/login/index.php"
    Then ".local-bgbling" "css_element" should not exist

  Scenario: The background layer is not shown on pages that are not ticked
    Given the following config values are set as admin:
      | enabled | 1 | local_bgbling |
    When I log in as "admin"
    Then ".local-bgbling" "css_element" should not exist
