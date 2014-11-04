## Release v0.2.0

Backwards compatibility was broken with v0.2.0 in order to get Symfony2 to
successfully autodetect Command classes.  Existing code, of which there is
expected to be little at this early point in the project's life, should adjust
the following:

 - Generally change namespaces from `\SymfonyCronBundle` to
   `\CourseHero\SymfonyCronBundle`;
 - Change namespaces from `\SymfonyCronBundle\Component\Console\Command` to
   `\CourseHero\SymfonyCronBundle\Command`.
 - Change any references in `AppKernel.php` to
   `\CourseHero\SymfonyCronBundle\CourseHeroSymfonyCronBundle`.
