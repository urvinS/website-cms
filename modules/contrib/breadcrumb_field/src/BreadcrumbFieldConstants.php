<?php


namespace Drupal\breadcrumb_field;


class BreadcrumbFieldConstants {

  /**
   * Module's name.
   */
  const MODULE_NAME = 'breadcrumb_field';

  /**
   * Flag for including or not the front page as a segment.
   */
  const INCLUDE_HOME_SEGMENT = 'include_home_segment';

  /**
   * Title for the front page segment.
   */
  const HOME_SEGMENT_TITLE = 'home_segment_title';

  /**
   * Flag for keeping the breadcrumb on the front page.
   */
  const HOME_SEGMENT_KEEP = 'home_segment_keep';

  /**
   * Flag for including or not the page's title as a segment.
   */
  const INCLUDE_TITLE_SEGMENT = 'include_title_segment';

  /**
   * Flag for printing the page's title as a link, or printing it as a text.
   */
  const TITLE_SEGMENT_AS_LINK = 'title_segment_as_link';

  /**
   * Use the page's title when it is available.
   */
  const TITLE_FROM_PAGE_WHEN_AVAILABLE = 'title_from_page_when_available';

  /**
   * Flag for storing single home item settings.
   */
  const HIDE_SINGLE_HOME_ITEM = 'hide_single_home_item';

  /**
   * Default list of excluded paths.
   *
   * @return array
   *   Default list of ignored paths.
   */
  public static function defaultExcludedPaths() {
    static $default_excluded_paths = [
      'search',
      'search/node',
    ];

    return $default_excluded_paths;
  }

  /**
   * Default list of replaced titles.
   *
   * @return array
   *   Default list of replaced titles.
   */
  public static function defaultReplacedTitles() {
    static $default_replaced_titles = [];

    return $default_replaced_titles;
  }

  /**
   * Default list of ignored words.
   *
   * @return array
   *   Default list of ignored words.
   */
  public static function defaultIgnoredWords() {
    static $default_ignored_words = [
      'of',
      'and',
      'or',
      'de',
      'del',
      'y',
      'o',
    ];

    return $default_ignored_words;
  }

}
