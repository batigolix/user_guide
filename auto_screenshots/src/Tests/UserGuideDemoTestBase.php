<?php

namespace Drupal\auto_screenshots\Tests;

use Drupal\Core\Site\Settings;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for tests that build demo sites for User Guide and screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * To make a class for a new language:
 * - Extend this class.
 * - Override the $demoInput member variable, translating the input into the
 *   target language. Note that most of the text should not contain
 *   ' characters, as this will result in an error when generating the screen
 *   shots.
 */
abstract class UserGuideDemoTestBase extends WebTestBase {

  /**
   * Strings and other information to input into the demo site.
   *
   * @var array
   */
  protected $demoInput = [
    // Default and second languages for the site.
    'first_langcode' => 'en',
    'second_langcode' => 'es',

    // Basic site information.
    'site_name' => 'Anytown Farmers Market',
    'site_slogan' => 'Farm Fresh Food',
    'site_mail' => 'info@example.com',
    'site_default_country' => 'US',
    'date_default_timezone' => 'America/Los_Angeles',

    // Home page content item.
    'home_title' => 'Home',
    'home_body' => "<p>Welcome to City Market - your neighborhood farmers market!</p>\n<p>Open: Sundays, 9 AM to 2 PM, April to September</p>\n<p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_path' => '/home',
    'home_revision_log_message' => 'Updated opening hours',

    // About page content item.
    'about_title' => 'About',
    'about_body' => "<p>City Market started in April 1990 with five vendors.</p>\n<p>Today, it has 100 vendors and an average of 2000 visitors per day.</p>",
    'about_path' => '/about',



    // Vendor content type settings.
    'vendor_type_name' => 'Vendor',
    'vendor_type_description' => 'Information about a vendor',
    'vendor_type_title_label' => 'Vendor name',

    // Recipe content type settings.
    'recipe_type_name' => 'Recipe',
    'recipe_type_description' => 'Recipe submitted by a vendor',
    'recipe_type_title_label' => 'Recipe name',
  ];

  /**
   * For our demo site, start with the standard profile install.
   */
  protected $profile = 'standard';

  /**
   * Modules needed for this test.
   *
   * @todo 'language' and 'locale' are currently needed to get around this bug:
   *   https://www.drupal.org/node/2573975
   *   When that is fixed, it may be possible to enable them later in the
   *   process, but they'll be needed when the language is added at the top
   *   of the test, so might as well just turn them on here.
   */
  public static $modules = ['update', 'language', 'locale'];

  /**
   * We need verbose logging to be on.
   */
  public $verbose = TRUE;

  /**
   * Counter for screenshot output, separate from regular verbose IDs.
   */
  protected $screenshotId = 0;

  /**
   * Builds the entire demo site and makes screenshots.
   *
   * Note that the method name starts with "test" so that it will be detected
   * as a "test" to run.
   */
  public function testBuildDemoSite() {
    $this->drupalLogin($this->rootUser);

    // Add the first language, set the default language to that, and delete
    // English, to simulate having installed in a different language. No
    // screen shots for this!
    if ($this->demoInput['first_langcode'] != 'en') {
      // Note that here the buttons should still be in English until after
      // the other language is set as the default language.
      $this->drupalPostForm('admin/config/regional/language/add', [
          'predefined_langcode' => $this->demoInput['first_langcode'],
        ], 'Add language');
      $this->drupalPostForm('admin/config/regional/language', [
          'site_default_language' => $this->demoInput['first_langcode'],
        ], 'Save configuration');
      // From this point on, everything should be translated into the first
      // language.
      $this->drupalPostForm('admin/config/regional/language/delete/en', [], $this->callT('Delete'));
    }

    // Figure out where the assets directory is.
    $dir_parts = explode('/', drupal_get_path('module', 'auto_screenshots'));
    array_pop($dir_parts);
    $assets_directory = implode('/', $dir_parts) . '/assets/';

    // Set up a red border CSS style for outlining portions of images.
    $red_border = '2px solid #e62600;';

    // Topic: config-basic - Edit basic site information.
    $this->drupalPostForm(NULL, [
        'site_name' => $this->demoInput['site_name'],
        'site_slogan' => $this->demoInput['site_slogan'],
        'site_mail' => $this->demoInput['site_mail'],
      ], $this->callT('Save configuration'));
    $this->assertText($this->callT('The configuration options have been saved.'));

    // In this case, we want the screen shot made after we have entered the
    // information, because for a normal user, this information would have
    // been set up during the install.
    $this->drupalGet('admin/config/system/site-information');
    $this->setUpScreenShot('config-basic-SiteInfo.png', [550, 275, 30, 200]);

    // Date and time configuration, same topic.
    $this->drupalGet('admin/config/regional/settings');
    $this->drupalPostForm(NULL, [
      'site_default_country' => $this->demoInput['site_default_country'],
      'date_default_timezone' => $this->demoInput['date_default_timezone'],
      'configurable_timezones' => FALSE,
      ], $this->callT('Save configuration'));
    $this->assertText($this->callT('The configuration options have been saved.'));

    // In this case, we want the screen shot made after we have entered the
    // information, because for a normal user, this information would have
    // been set up during the install.
    $this->drupalGet('admin/config/regional/settings');
    $this->setUpScreenShot('config-basic-TimeZone.png', [550, 275, 30, 200]);

    // Topic: config-uninstall - Uninstall unused modules.
    $this->drupalGet('admin/modules/uninstall');
    // For this screen shot, scroll to the bottom and make sure the
    // Search checkbox is checked, using JavaScript.
    $this->setUpScreenShot('config-uninstall_searchModUninstall.png', [550, 275, 30, 200], 'onLoad="window.scroll(0,2000); jQuery(\'#edit-uninstall-search\').attr(\'checked\', 1);"');
    $this->drupalPostForm(NULL, [
        'uninstall[search]' => TRUE,
      ], $this->callT('Uninstall'));
    $this->assertText($this->callT('Would you like to continue with uninstalling the above?'));
    $this->assertText($this->callT('Search'));
    $this->setUpScreenShot('config-uninstall_confirmUninstall.png', [550, 275, 30, 200]);
    $this->drupalPostForm(NULL, [], $this->callT('Uninstall'));
    $this->assertText($this->callT('The selected modules have been uninstalled.'));

    // Follow-on task: also uninstall Comment and History. No screen shots.
    // Before removing Comment, we have to remove the Comment field.
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.comment/delete', [], $this->callT('Delete'));
    $this->drupalPostForm('admin/structure/comment/manage/comment/delete', [], $this->callT('Delete'));
    $this->drupalGet('admin/modules/uninstall');
    $this->drupalPostForm(NULL, [
        'uninstall[comment]' => TRUE,
        'uninstall[history]' => TRUE,
      ], $this->callT('Uninstall'));
    $this->assertText($this->callT('Would you like to continue with uninstalling the above?'));
    // Module names are not translated.
    $this->assertText('History');
    $this->assertText('Comment');
    $this->drupalPostForm(NULL, [], $this->callT('Uninstall'));
    $this->assertText($this->callT('The selected modules have been uninstalled.'));

    // Topic config-user: Configuring user account settings.
    $this->drupalGet('admin/config/people/accounts');
    $this->drupalPostForm(NULL, [
        'user_register' => 'admin_only',
      ], $this->callT('Save configuration'));
    $this->assertText($this->callT('The configuration options have been saved.'));
    // For this screen shot, scroll down 500px.
    $this->setUpScreenShot('config-user_account_reg.png', [550, 275, 30, 200], 'onLoad="window.scroll(0,500);"');
    // For this screen shot, scroll down to the bottom and open the Account
    // activation vertical tab.
    $this->setUpScreenShot('config-user_email.png', [550, 275, 30, 200], 'onLoad="window.scroll(0,5000); jQuery(\'#edit-email-activated\').click();"');

    // Topic config-theme: Configuring the theme.
    $this->drupalGet('admin/appearance');
    $this->assertText('Bartik');
    $this->assertLink($this->callT('Settings'));
    $this->setUpScreenShot('config-theme_bartik_settings.png', [550, 275, 30, 200]);
    $this->drupalGet('admin/appearance/settings/bartik');
    // For this screenshot, before the setting are changed, use JavaScript to
    // scroll down to the bottom, uncheck Use the default logo, and outline
    // the logo upload box.
    $this->setUpScreenShot('config-theme_logo_upload.png', [550, 275, 30, 200], 'onLoad="widow.scroll(0,5000); jQuery(\'#edit-default-logo\').click(); jQuery(\'#edit-logo-upload\').css(\'border\', \'' . $red_border . '\');"');

    $this->drupalPostForm(NULL, [
        'scheme' => '',
        'palette[top]' => '#7db84a',
        'palette[bottom]' => '#2a3524',
        'palette[bg]' => '#ffffff',
        'palette[sidebar]' => '#f8bc65',
        'palette[sidebarborders]' => '#e96b3c',
        'palette[footer]' => '#2a3524',
        'palette[titleslogan]' => '#ffffff',
        'palette[text]' => '#000000',
        'palette[link]' => '#2a3524',
        'default_logo' => FALSE,
        'logo_path' => $assets_directory . 'AnytownFarmersMarket.png',
      ], $this->callT('Save configuration'));
    $this->assertText($this->callT('The configuration options have been saved.'));
    $this->assertRaw($assets_directory);

    // For this screen shot, scroll down so the color wheel is in view.
    $this->setUpScreenShot('config-theme_color_scheme.png', [550, 275, 30, 200], 'onLoad="window.scroll(0,600);"');
    // For this screen shot, scroll down to the bottom so the preview is in
    // view.
    $this->setUpScreenShot('config-theme_color_scheme_preview.png', [550, 275, 30, 200], 'onLoad="window.scroll(0,5000);"');

    $this->drupalGet('<front>');
    $this->setUpScreenShot('config-theme_final_result.png', [550, 275, 30, 200]);

    // Topic: language-enable - Installing multilingual modules
    $this->drupalGet('admin/modules');
    // For this screen shot, scroll to the bottom and make sure the
    // modules are checked, using JavaScript.
    $this->setUpScreenShot('language-enable-check-modules.png', [550, 275, 30, 200], 'onLoad="window.scroll(0,2000); jQuery(\'#module-config-translation\').attr(\'checked\', 1); jQuery(\'#module-content-translation\').attr(\'checked\', 1);"');
    $this->drupalPostForm(NULL, [
      'modules[Multilingual][config_translation][enable]' => TRUE,
      'modules[Multilingual][content_translation][enable]' => TRUE,
      ], $this->callT('Install'));

    // Topic: language-add - Adding a language
    $this->drupalGet('admin/config/regional/language');
    $this->drupalGet('admin/config/regional/language/add');
    $this->drupalPostForm(NULL, [
      'predefined_langcode' => $this->demoInput['second_langcode'],
      ], $this->callT('Add language'));
    // Simple screenshot.
    $this->setUpScreenShot('language-add-list.png', [550, 275, 30, 200]);

    // Topic: language-content-config - Configuring Content Translation
    $this->drupalGet('/admin/config/regional/content-language');
    // Simple screenshot of top section.
    $this->setUpScreenShot('language-content-config_custom.png', [550, 275, 30, 200]);
    // For this screenshot, we need to check Content, and then under
    // Article and Basic Page, click the Show language selector button. Also
    // under Basic page, simulate expanding the drop-down for default language
    // by setting the 'size' attribute.
    $this->setUpScreenShot('language-content-config_content.png', [550, 275, 30, 200], 'onLoad="jQuery(\'#edit-entity-types-node\').attr(\'checked\', 1); jQuery(\'#edit-entity-types-block-content\').attr(\'checked\', 1); jQuery(\'#edit-entity-types-menu-link-content\').attr(\'checked\', 1); jQuery(\'#edit-settings-node\').show(); jQuery(\'#edit-settings-node-article-settings-language-language-alterable\').attr(\'checked\', 1); jQuery(\'#edit-settings-node-page-settings-language-langcode\').attr(\'size\', 7);"');

    $this->drupalPostForm(NULL, [
        'entity_types[node]' => TRUE,
        'entity_types[block_content]' => TRUE,
        'entity_types[menu_link_content]' => TRUE,

        'settings[node][page][translatable]' => TRUE,
        'settings[node][page][fields][title]' => TRUE,
        'settings[node][page][fields][uid]' => FALSE,
        'settings[node][page][fields][status]' => FALSE,
        'settings[node][page][fields][created]' => FALSE,
        'settings[node][page][fields][changed]' => FALSE,
        'settings[node][page][fields][promote]' => FALSE,
        'settings[node][page][fields][uid]' => FALSE,
        'settings[node][page][fields][sticky]' => FALSE,
        'settings[node][page][fields][path]' => TRUE,
        'settings[node][page][fields][body]' => TRUE,

        'settings[block_content][basic][translatable]' => TRUE,
        'settings[block_content][basic][fields][info]' => TRUE,
        'settings[block_content][basic][fields][changed]' => FALSE,
        'settings[block_content][basic][fields][body]' => TRUE,

        'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][title]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][description]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][changed]' => FALSE,
      ], $this->callT('Save configuration'));
    // Screenshot of the Basic page area after saving the configuration.
    $this->setUpScreenShot('language-content-config_custom.png', [550, 275, 30, 200]);

    // Topic: content-create - Creating a Content Item
    $this->drupalGet('node/add/page');
    // Screen shot with title, body, and alias filled in, and alias area
    // expanded. The body is in an editor that is an iframe, so for the screen
    // shot, replace the iframe with a text area.
    $this->setUpScreenShot('content-create-create-basic-page.png', [550, 275, 30, 200], 'onLoad="jQuery(\'#edit-title-0-value\').val(\'' . $this->demoInput['home_title'] . '\'); jQuery(\'iframe\').replaceWith(\'<textarea rows=10 cols=200>' . $this->demoInput['home_body'] . '</textarea>\'); jQuery(\'#edit-path-settings\').show(); jQuery(\'#edit-path-0-alias\').val(\'' . $this->demoInput['home_path'] . '\');');
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['home_title'],
        'body[0][value]' => $this->demoInput['home_body'],
        'path[0][alias]' => $this->demoInput['home_path'],
      ], $this->callT('Save and publish'));

    // Follow-on task: create About page.
    $this->drupalPostForm('node/page/add', [
        'title[0][value]' => $this->demoInput['about_title'],
        'body[0][value]' => $this->demoInput['about_body'],
        'path[0][alias]' => $this->demoInput['about_path'],
      ], $this->callT('Save and publish'));

    // Topic: content-edit - Editing a content item
    $this->drupalGet('admin/content');
    // Simple screenshot of content list.
    $this->setUpScreenShot('content-edit-admin-content.png', [550, 275, 30, 200]);
    $this->drupalGet('node/1/edit');
    // Screen shot of the revision area of the edit page, with revision
    // information filled in.
    $this->setUpScreenShot('content-edit-revision.png', [550, 275, 30, 200], 'onLoad="jQuery(\'#edit-revision\').attr(\'checked\', 1); jQuery(\'#edit-revision-log-wrapper\').show(); jQuery(\'#edit-revision-log-0-val\').val(\'' . $this->demoInput['home_revision_log_message'] . '\');');
    // Submit the revision.
    $this->drupalPostForm(NULL, [
        'revision_log[0][value]' => $this->demoInput['home_revision_log_message'],
      ], $this->callT('Save and keep published'));
    // Simple screenshot of top of page with updated message.
    $this->setUpScreenShot('content-edit-message.png', [550, 275, 30, 200]);

    // Topic: content-in-place-edit - Editing with the In-Place Editor
    $this->drupalGet('node/2');
    // @todo: Determine whether the screen shots in this topic can be
    // auto-generated or not, since they are heavily JavaScript dependent.
    // Possibly all the clicks and hovers can be simulated? Not sure.
    $this->setUpScreenShot('test-in-place-edit.png', [550, 275, 30, 200]);

    // Topic: menu-home - Designating a Front Page for your Site
    // @todo This is the next topic to do.


    // Topic: structure-content-type - Adding a Content Type
    // fix! add screen shots in this section
    // Create the Vendor content type.
    $this->drupalGet('admin/structure/types');
    $this->drupalGet('admin/structure/types/add');
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['vendor_type_name'],
        'description' => $this->demoInput['vendor_type_description'],
        'title_label' => $this->demoInput['vendor_type_title_label'],
        'options[promote]' => FALSE,
        'options[revision]' => TRUE,
        'display_submitted' => FALSE,
        'menu_options[main]' => FALSE,
        'language_configuration[content_translation]' => TRUE,
      ], $this->callT('Save and manage fields'));

    // Follow-on task for structure-content-type - Add content type for Recipe
    // No screen shots.
    $this->drupalGet('admin/structure/types');
    $this->drupalGet('admin/structure/types/add');
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['recipe_type_name'],
        'description' => $this->demoInput['recipe_type_description'],
        'title_label' => $this->demoInput['recipe_type_title_label'],
        'options[promote]' => FALSE,
        'display_submitted' => FALSE,
        'menu_options[main]' => FALSE,
        'language_configuration[content_translation]' => TRUE,
      ], $this->callT('Save and manage fields'));

    // Topic: structure-content-type-delete - Deleting a Content Type
    // fix! add screen shots in this section
    // Delete the Article content type.
    $this->drupalGet('admin/structure/types');
    $this->drupalGet('admin/structure/types/manage/article/delete');
    $this->drupalPostForm(NULL, [], $this->callT('Delete'));

  }

  /**
   * Clears the Drupal cache.
   *
   * @todo Remove this if it is not used.
   */
  protected function clearCache() {
    $this->drupalPostForm('admin/config/development/performance', [], $this->callT('Clear all caches'));
  }

  /**
   * Calls t() in the user interface, with the site's first language.
   *
   * For some unknown reason, when running this in non-English languages, the
   * form submits etc. are not working because it is not looking for the
   * correct (translated) button text when you make a call like
   * @code
   * $this->drupalPostForm('url/here', [], t('Button name'));
   * @endcode
   * So this method wraps t() by passing in the language code to translate
   * to, which is easier than trying to figure out what the real problem is.
   *
   * @param string $text
   *   Text to pass into t(). Must have been defined by another module or it
   *   will not be in the translation database.
   * @param bool $first
   *   (optional) TRUE (default) to translate to the first language in the
   *   demoInput member variable; FALSE to use the second language.
   *
   * @return string
   *   Original string, translated string, or a wrapper object that can be used
   *   like a string.
   */
  protected function callT($text, $first = TRUE) {
    if ($first) {
      $langcode = $this->demoInput['first_langcode'];
    }
    else {
      $langcode = $this->demoInput['second_langcode'];
    }

    if ($langcode == 'en') {
      return $text;
    }

    return t($text, [], ['langcode' => $langcode]);
  }

  /**
   * Makes clean screenshot output, and adds a note afterwards.
   *
   * The screen shot is of the current page.
   *
   * @param string $file
   *   Name of the screen shot file.
   * @param int[] $geometry
   *   Geometry of the screen shot, in pixels: width, height, and offset
   *   x and y.
   * @param string $body_addition
   *   Additional text to add into the HTML body tag. Example:
   *   'onLoad="window.scroll(0,500);"'.
   */
  protected function setUpScreenShot($file, $geometry, $body_addition = '') {
    $output = str_replace('<body ', '<body ' . $body_addition . ' ', $this->getRawContent());

    // This is like TestBase::verbose() but just the bare HTML output, and
    // with a separate file counter so it doesn't interfere.
    $screenshot_filename =  $this->verboseClassName . '-screenshot-' . $this->screenshotId . '-' . $this->testId . '.html';
    if (file_put_contents($this->verboseDirectory . '/' . $screenshot_filename, $output)) {
      $url = $this->verboseDirectoryUrl . '/' . $screenshot_filename;
      $link = '<a href="' . $url . '" target="_blank">Screen shot output</a>';
      $this->error($link, 'User notice');
    }
    $this->screenshotId++;
    $this->pass('SCREENSHOT: ' . $file . ' ' . implode(' ', $geometry) . ' ' . $url);
  }

  /**
   * Prepares site settings and services before installation.
   *
   * Overrides WebTestBase::prepareSettings() so that we can store public
   * files in a directory that will not get removed until the verbose output
   * is gone.
   */
  protected function prepareSettings() {
    parent::prepareSettings();

    $this->publicFilesDirectory = $this->verboseDirectory . '/' . $this->databasePrefix;
    $settings['settings']['file_public_path'] = (object) [
      'value' => $this->publicFilesDirectory,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    Settings::initialize(DRUPAL_ROOT, $this->siteDirectory, $this->classLoader);
  }

}
