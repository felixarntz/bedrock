# Custom Bedrock with WP Project Scaffolding MU-Plugin

This is a customized fork of the popular [Bedrock](https://roots.io/bedrock/) boilerplate for WordPress projects. [Read the original Bedrock readme...](https://github.com/roots/bedrock)

## Customizations

* bundles a must-use plugin called **WP Project Scaffolding** to handle a lot of basic project functionality
  * it contains the following base classes (these are always loaded)
    * *Config* - the usual WordPress configuration, but more flexible: constants are automatically defined from environment vars, from the `composer.json` file's `->extra->constants` and from default values, in that priority (this class is instantiated from `wp-config.php`)
    * *PluginAutoloader* - allows loading must-use plugins just like normal plugins (copied from the original Bedrock project)
    * *Sunrise* - multisite domain mapping (this class is instantiated from `sunrise.php`)
    * *ThemeFallback* - handles the default theme directory (copied from the original Bedrock project)
  * it contains the following module classes (optional, can be enabled via `composer.json` file's `->extra->settings`; those settings are passed to the respective class which handles them)
    * *AutoUpdater* - handles WordPress Auto Updates, allows to easily adjust which parts of WordPress are auto-updated
    * *GithubUpdater* - if the Github Updater is used, this allows to specify access tokens to private repositories and more
* `/config/` directory is gone, configuration is instead handled by the custom *Config* class (the three environments from the original Bedrock are still supported though)
* WordPress is now installed into `/web/core/` instead of `/web/wp`
* some dependencies have been adjusted
