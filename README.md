# TYPO3 Site Operator
The «site operator» is a utility extension that helps you
configure and run your TYPO3 instance. It makes some tedious
tasks more easy and fun.

## Features
(see details and how-to further down below)
- Removes the need for TypoScript templates and automatically
  includes those from your site package (respecting
  application context).
- Provides a dead-simple way to set up email messages to be
  rendered via Fluid, including any assets such as images and
  css.
- Distributes common values (such as ids, colors, paths, anything
  really) to whatever application level they're needed, defining
  them in one single place only! You might need color definitions
  in a 'manifest.json' file, but of course the same values are
  needed in your (s)css as well.
- Generates «static resources» in general, such as images or
  any text-based files (see above). So, it's possible to
  pre-render favicons, but also SVG or TypoScript files.
- Takes pre-configured scheduler tasks and makes sure they're
  installed on deployment / installation.
- Provides an object/chaining-based approach to TCA building. This
  enables you to build pretty large TCA structures with
  with 10-20 chainable, meaningful method calls.
- Provides various site- or environment-related utility methods.
- Smoke-tests your sites in any context with some easily
  maintainable and transparently declared configuration.

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

### Automatic TS template setup
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


### HTML & plain text emails
Set up HTML email in 3-4 lines of code. Any email sent via
the regular TYPO3 `MailMessage` core class will be extended
to use Fluid templates. Just as important: images and css
are integrated very (very!) easily.

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
   MailMessage::setDefaultTemplatePathAndFilename(
       'EXT:my_site_package/Resources/Private/Templates/Email/Text.html',
       'EXT:my_site_package/Resources/Private/Templates/Email/Html.html',
   );
   MailMessage::setDefaultCssFilePath('EXT:my_site_package/Resources/Public/Css/email.css');
   MailMessage::addDefaultEmbeddable('logo', 'EXT:my_site_package/Resources/Public/Images/email-logo.png');
   ```

    Templates and CSS may optionally be limited to a specific site (on
    multi-site setups), using the third parameter:
    ```PHP 
    MailMessage::setDefaultTemplatePathAndFilename(
        'EXT:my_site_package/Resources/Private/Templates/Email/Text.html',
        'EXT:my_site_package/Resources/Private/Templates/Email/Html.html',
        'site-foo'
    );
    MailMessage::setDefaultCssFilePath('EXT:.../styles.css', 'site-foo');
    ```

2. Provide *Fluid email template*s
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

#### General behavior
Any default settings / assets may be overridden or extended
on a case-to-case basis using the API methods
- `setTemplatePathAndFilename`
- `setCssFilePath`
- `addEmbeddable`

... after creating a `MailMessage` instance.

At this time, the 'setBody' method will detect whether
the given content is text or HTML and generate the
counterpart automagically, accordingly. You might want use
the `$contentType` argument, if the detection fails though.

```php
(...)
public function setBody($body, $contentType = null, $charset = null)
{
(...)
```
  
 
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

The site-related smoke tests automatically run per site and per language,
but may be restricted by the according configuration option
(see examples).

Configuring these tests is super straight forward and even
*introducing your very own processor is a matter of minutes!*


### Utility methods
```
// Application context detection
$isInAnyLocalContext = ProjectInstance::runsInApplicationContext(null, 'local');
$isInTheLiveProductionContext = ProjectInstance::runsInApplicationContext('Production', 'Live');

// Retrieve site package key (extension name).
// Will only work, after ProjectInstance::initialize('site_package');
// has been called (see Documentation/Examples/AdditionalConfiguration.php)).
$sitePackageKey = ProjectInstance::getSitePackageKey();

// Will output the current tag and revision of your instance's
// git repository state. P.e. "2.5.0 / eb6eae4ff4"
ProjectInstance::getApplicationVersion();
```
