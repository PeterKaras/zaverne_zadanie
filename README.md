# symfony-api-template


# TODO
Replace your repo in `.github/workflows/build.yml:36`

# Start dev
```bash
docker compose up -d web
```

## Composer 
Install composer 
```bash
docker compose run cli composer install
```


## JWT
Docs: https://github.com/lexik/LexikJWTAuthenticationBundle

generate JWT keypair
```bash
docker compose run cli php bin/console lexik:jwt:generate-keypair
```

## Entities
Create database
```bash
docker compose run cli php bin/console doctrine:database:create
```

Edit or Create entity
```bash
docker compose run cli php bin/console make:entity {name}
```

Create migration
```bash
docker compose run cli php bin/console make:migration
```

Execute migration
```bash
docker compose run cli php bin/console doctrine:migrations:migrate
```