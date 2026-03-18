# Running Plugin Integration Tests Locally

First make sure that docker is installed and running on your operating system.
You can download and install Docker Desktop from here;
```
https://www.docker.com/products/docker-desktop/
```
Navigate to the repository root and run the following:
```bash
docker compose run --rm swpm
```
This will do the followings:
- Download and php-cli, wp-cli, composer, mysql, phpmyadmin and other necessary tools.
- Spin-up docker containers.
- Download and setup wordpress and wordpress test library.
- Start an interactive shell inside the `swpm` container to run phpunit test commands.

NOTE: All these tools will be installed inside docker environment, not directly inside the host machine. You may see `tmp` and `tests/vendor` folder, these are just mountings to host machine from inside the docker environment.

When the interactive shell (that actually runs inside the swpm container) in opened, you can run any linux commands there. Here we will be running `phpunit` commands.

To run php tests using the interactive shell
```bash
phpunit --testdox --verbose -c /app/tests/phpunit.xml.dist
```
Or,
To run php tests using composer alias, use:
```bash
composer test
```
This executes `phpunit` command underneath.

NOTE: You have to run this inside the `/app/tests` folder of the `swpm` container, which is the default directory when the interactive shell gets open.

### Thats all for running wp plugin integration test locally!

## Additional Instructions

To build docker images and start containers
```bash
docker compose up --build
```

To start containers in detached mode
```bash
docker compose up -d
```

To start an interactive shell
```bash
docker compose run --rm swpm
```

To run php tests using the interactive shell
```bash
phpunit --testdox --verbose -c /app/tests/phpunit.xml.dist
```
Or,
To run php tests using composer alias, use:
```bash
composer test
```


Get the wp plugin integration tests suite scaffolding script if deleted accidentally.
```bash
curl -o tests/bin/install-wp-tests.sh https://raw.githubusercontent.com/wp-cli/scaffold-command/main/templates/install-wp-tests.sh
```