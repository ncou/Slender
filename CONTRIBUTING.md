# How to Contribute

## Pull Requests

1. Fork the Slender Framework repository
2. Create a new branch for each feature or improvement
3. Send a pull request from each feature branch to the **master** branch

## Style Guide

All pull requests must adhere to the [PSR-2 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md).
(This may change in the future)

## Unit Testing

All pull requests must be accompanied by passing unit tests and complete code coverage. The Slender Framework uses phpunit for testing.
It is recommended that you run `composer test` before publishing a PR. This command will run both PHPUnit and the code sniffer 

Both PHPUnit and CodeSniffer are included as composer dev dependencies.
[Learn about PHPUnit](https://github.com/sebastianbergmann/phpunit/)
[Learn about CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
