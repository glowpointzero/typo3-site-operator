# TYPO3 Site Operator
The «site operator» is a utility extension that helps you
configure and run your TYPO3 instance. It makes some tedious
tasks more easy and fun.

---
## ! THIS PACKAGE IS NO LONGER MAINTAINED !

Due to the lack of time to invest into its development
and public interest, this package will no longer receive
updates.

---

## Features
(see details and how-to further down below)

- **[Distribution of common values and generating of static
  assets](#distribution-of-common-values--generating-static-assets)**:
  - Distribute values (such as ids, colors, paths, anything
    really) to whatever application level they're needed, defining
    them in one single place only! You might need color definitions
    in a 'manifest.json' file, but of course the same values are
    needed in your (s)css as well.
  - Generate «static resources» in general, such as images or
    any text-based files (see above). So, it's possible to
    pre-render favicons, but also SVG or TypoScript files.
- **[Scheduled tasks auto-setup](#scheduled-tasks-auto-setup)** takes pre-configured
  scheduler tasks and makes sure they're installed on
  deployment / installation.
- **[Symlinker](#symlinker)** will symlinks to any files/directories
  as configured. Multiple source paths are possible, asking
  the user to choose interactively.
- **[TCA Builder](#tca-builder-with-speaking-chainable-methods)** provides an object/chaining-based approach
  to creating your models' TCAs in a very comfortable, transparent
  and chainable way.
- 'Site checkup' **[smoke-tests](#smoke-tests)** your sites with some easily
   maintainable and transparently declared configuration.
- Dead-simple way to **[set up HTML email messages](#html--plain-text-emails)**.
  Rendered via Fluid, including any assets such as images and
  css.
- **[Automatic TypoScript template setup](#automatic-typoscript-template-setup)** removes the
  need to create TypoScript template reords on the root
  pages of your sites. It automatically includes those
  from your site package. If you want to, it even respects
  the current application context.

**Check out [the examples in the documentation directory](Documentation/Examples).
These will make sense to integrators/developers immediately.**

## Setup
1. Require the package `composer require glowpointzero/typo3-site-operator`
2. In your `AdditionalConfiguration.php`, initialize your
   TYPO3 project instance calling `\Glowpointzero\SiteOperator\ProjectInstance::initialize('my_site_package');`.
   This
   - will be used by the TypoScript data provider hook (see further down below)
   - enables you to remove static values from your setup, by retrieving
     the current site package (`ProjectInstance::getSitePackageKey()`)
3. Whenever the 'site operator configuration' (config.json) is mentioned,
   it refers to a json file, which should be located under
   './config/typo3-site-operator/config.json'. If there is no
   configuration found under this path, the following paths are
   also checked:
   - ./config/typo3-site-operator.json
   - ./typo3-site-operator.json
   - ./web/typo3conf/typo3-site-operator.json
   
   (assuming the current directory is your project directory and `web` is
   your public directory)
   
   You will be asked if you'd like this file to be
   created once you run a site operator command.
   
   *You may split up your configuration* in whatever way
   you like and reference all the separate configuration
   files in the 'includes' section of the main 'config.json'.


## Features
 
### Distribution of common values / generating static assets
- Distribute text-based values across the whole instance
  into arbitrary application layers (p.e. a color value
  to an XML or TS constants as a parameter for PWA theming,
  as well as into an scss file). You could even pre-generate
  variants of SVGs.
- Pre-generate static images (p.e. the favicon).
  See examples in [in the documentation directory](Documentation/Examples/GeneratedStaticResources)

#### How-to
- Extend the `generatedResources` section in the `config.json` (extend
  `constants` as well, for text-based resource generation):
  ```json
  (...)

  "constants": {
    "foo": {
        "bar": "baz"
    }
  },
  "generatedResources": {
      "web/typo3conf/(...)/pregenerated-constants.typoscript": {
        "generator": "text",
        "configuration": {
          "source": "web/typo3conf/(...)/pregenerated-constants.typoscript.dist"
        }
      },
      "web/(...)/prerendered.png": {
        "generator": "image",
          "configuration": {
            "source": "web/(...)/source-image.jpg",
            "parameters": {
              "colorspace": "sRGB",
              "resize": "250x250"
            }
          }
      }
  }

  (...)
  ```
  *example-constants.typoscript.dist*
  ```
    site.shared.barValue = [[typo3-site-operator:constants/foo/bar]]
  ```
  
Run `./vendor/bin/typo3cms operator:generateStaticResources` to process
the `generatedResources` configuration.



### Scheduled tasks auto-setup
Define any arbitrary number of scheduled tasks that should be
put in place when installing your site (requires
`typo3/cms-scheduler`).

#### How-to
- Create a simple PHP file returning an array of
  task objects (see [example in the documentation directory](Documentation/Examples/ScheduledTasks.php)).
- Reference your file in the `config.json` under the
  `scheduledTasks` section:
  ```json    
  "scheduledTasksSourcePaths": [
    "config/ScheduledTasks.php"
  ],
  ```
- Run `./vendor/bin/typo3cms operator:installScheduledTasks`.
  You can do this automated (p.e. via composer), as
  the task-registering process will always check, if there
  is a task of the same class already registered and prevent
  duplicates.


### Symlinker
The `symlink` command will take any target path
from `symlinks` in the site operator configuration and
will attempt to link it to its source. If multiple
sources are defined, the user will be prompted to choose
one of the sources that should be linked.
Example configuration (from the 'Documentation' directory):
```
{
  "symlinks": {
    "web/typo3conf/AdditionalConfiguration.php": {
      "source": "config/my/externalized/AdditionalConfiguration.php"
    },
    "web/fileadmin/linked_directory_selectable_by_user": {
      "sources": [
        "some/directory",
        "/another/path/on/your-system",
        "/yet-another/path/on/your-system"
      ]
    }
  }
}
```


### TCA builder with speaking, chainable methods
The TCA builder provides a comfortable TCA specification
experience using a simple approach, allowing chaining,
using easy-to-understand language. Also, all paths to the
properties' labels are automatically generated and only
need to be provided in the right place.

Note that at this time, this feature is laid out to
cater to the needs of new **models**, but not (yet)
to **extend** existing TCA definitions.

#### How-to
*Configuration/TCA/MyModel.php*
```php
$tca = TcaBuilder::create(
    'my_extension',
    'MyModel',
    '[optional table name override]',
    '[optional xlf path override]'
);
$tca
  ->setDeletedColumnName('deleted')
  ->setDisabledColumnName('hidden')
  ->addDefaultSorting('start_date_time', 'DESC')
  
  ->addDateTimeColumn('mydatetime')
      ->andOverruleConfigurationWith([
            (your overrides here)
      ])
    ->toPalette('datesAndTimes')
    ->toTab('advanced')
  
  ->addSingleLineInputColumn('title')
    ->toPalette('title')
    ->toTab('general')

$myTca = $tca->toArray();
// You can still customize the TCA here, if needed
// $myTca['columns'] ....

return $myTca;
```

- If not overridden in the `TcaBuilder::create` call, *column*
  labels will be attempted to be retrieved in
  `EXT:my_extension/Resources/Private/Language/Model/MyModel.xlf:[lower camel case column name]`
- If not overridden in the `TcaBuilder::create` call, *grouping* labels (tabs
  and palettes) will be attempted to be retrieved in
  `EXT:my_extension/Resources/Private/Language/Model/MyModel.xlf:propertyGroup.[palette or tab id]`


### Smoke tests
Smoke testing is done via the `operator:siteCheckup`
command, which takes its tasks from the according
configuration key ('siteCheckup') in your site operator
configuration. This package has some inbuilt test classes
(called "processors"):
- HTTP response processor (check return headers *and* content)
- Scheduled tasks processor (basically checks, whether cronjobs are running)
- XML sitemap processor (resolves given sitemaps of all available sites)
- Variable processor (simple variable matching tests to
  validate an instance's configuration for example).

Configuring these tests is super straight forward and even
*introducing your very own processor is super simple!* Have
a look at the existing processors and configuration.

- Note that each criterion (p.e. `{"statusCode": "404"}`) must
  pass the test to make the whole test pass.
- Comparison values starting with a forward slash ('/') will
  be treated as a regular expression pattern.

Example:
```
{
  siteCheckup: {
    "404-page returns 404 status and 'noindex, nofollow'": {
      "processor": "\\Glowpointzero\\SiteOperator\\SiteCheckup\\Processors\\HttpResponseProcessor",
      "arguments": {
        "location": "foo/bar/shouldnt-exist",
        "successCriteria": [
          {"statusCode": "404"},
          {"content": "/<meta name=\"robots\" content=\"noindex(,nofollow)?\" \\/>/i"}
        ]
      }
    }
  }
}
```

### HTML & plain text emails
Set up HTML email in 3-4 lines of code. Any email sent via
the regular TYPO3 `FluidEmail` core class will be extended
so that it is super simple to embed images and CSS. The examples
mentioned even show how easy it is to 

Passed HTML content (`setBody(...)`) is automatically converted to
text, reformatting commonly used HTML structures (p.e. `<tr>`
elements become individual lines).

Two important notes:
1. Installing this extension will extend the core's 'MailMessage'
   class, allowing any content to be rendered into mixed
   (plain text / HTML) content. If no template paths are provided,
   the core's default behavior is used!
2. An initiative by [Georg Ringer](//twitter.com/georg_ringer) for a more
   thorough approach to provide multipart email messages is currently on its way
   and will - most probably - shipped with TYPO3 10 LTS (and provided for
   TYPO3 9 LTS as an extension): https://github.com/georgringer/templatedMail
   So, this feature of the site operator might become obsolete at some
   point. But it's not like you'd be trashing a lot of code later, if you still
   use it for the time being, eh?

#### How-to
See Fluid examples [in the documentation directory](Documentation/Examples/EmailTemplates)
for reference and available variables!

1. Extend *AdditionalConfiguration.php*
   ```PHP
   \Glowpointzero\SiteOperator\Configuration::enableAdvancedFluidEmails();

   EmailMessage::setDefaultCssFilePath('EXT:my_site_package/Resources/Public/Css/email.css');
   EmailMessage::addDefaultEmbeddable('logo', 'EXT:my_site_package/Resources/Public/Images/email-logo.png');
   ```

    Embeddables and CSS may optionally be limited to a specific site (on
    multi-site setups), using the third parameter:
    ```PHP 
    EmailMessage::addDefaultEmbeddable('logo', 'EXT:my_site_package/Resources/Public/Images/email-logo-site-x.png', 'site-foo');
    EmailMessage::setDefaultCssFilePath('EXT:.../styles-site-foo.css', 'site-foo');
    ```

2. The embeddables and CSS are included in the Fluid templates like so:
   ```HTML
   (...)
   <style type="text/css">{css}</style>
   (...)
      <img src="{embeddables.logo}" />
   (...)
      <strong>{subject}</strong><br />
      {content}
   (...)
   ```

#### Templating
This package comes with a very simple setup of templates,
layouts and partials that include rendering/inclusion
of CSS too. It replaces the 'Default' message as well
as the 'System' message templating that gets used for
alerts for example. The templating may be of course be 
overridden. Have a look into the 'Resources/Public/'.

#### Overriding default 
Any default settings / assets may be overridden or extended
on EmailMessage instances using the API methods
- `setCssFilePath`
- `addEmbeddable`

Example:
```PHP
// We're automatically getting an instance of 'EmailMessage'
// here, as the core's 'FluidEmail' class has been xclassed
// by our 'enableAdvancedFluidEmails' call in configuration.
$mailMessage = GeneralUtility::makeInstance(FluidEmail::class);
$mailMessage->setCssFilePath('path-that-overrides/default-styles.css');
```   

### Automatic TypoScript template setup
Automatically include `setup.typoscript` as well as `constants.typoscript` of your
site package, removing the need for the database TS template record.
These assets are included *context-dependent*, allowing for different
setup depending on the current environment (see below).

#### How-to
*AdditionalConfiguration.php*
```PHP
\Glowpointzero\SiteOperator\DataProvider\TemplateDataProviderHook::registerHook();
```
Done!

Assuming, we're in the application context "Development/Foo" and you've called
`ProjectInstance::initialize('my_site_package');` before, this will find and
include the first file found in the list of these paths:
- EXT:my_site_package/Configuration/TypoScript/setup.development.foo.typoscript
- EXT:my_site_package/Configuration/TypoScript/setup.development.typoscript
- EXT:my_site_package/Configuration/TypoScript/setup.typoscript

The constants found in the same path(s) will also be
included accordingly.
