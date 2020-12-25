## Setup Dev Environment
This repository features a docker environment to provide isolated PHP environment for development and testing purposes.

1. Make sure Docker is installed on you machine.
2. Start the docker service: `docker-compose up -d laravel-once`
3. Install the dependencies: `docker-compose exec laravel-once composer install`

Now you can use the container to:

1. Run the tests:

```bash
> docker-compose exec laravel-once composer run-script test
```

2. Validate the code-style

```bash
> docker-compose exec laravel-once composer run-script cs

# In order to fix the issue automatically:

> docker-compose exec laravel-once composer run-script cbf
```

### Git Hooks
The hooks ensure that you commit meets the minimum required quality, before pushing the changes to the remote repository.
In order to use the hooks, either copy the `pre-commit` file to your `.git/hooks/` directory, or reconfigure the repository:
```bash
> git config --local core.hooksPath .githooks/
```

If you are using the provided docker container, you need to execute the git commands inside the container
