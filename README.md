## Symfony container builder

Compile PHAR file:

```text
php --define phar.readonly=0 ./compile
```

Run output PHAR in your symfony project folder:

```txt
./orchestrator.phar build /path/to/symfony-project
```

## TODO

- Orchestrator::load
