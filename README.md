# PHP_Api
### Steps

1. `git clone https://github.com/kristjan-steinfeldt/PHP_Api`
2. Replace database.php variables with your own sql variables 
3. `sudo mysql -u root -p < doc/database.sql`


#Setting up SWAGGER
### Steps

1. `git clone https://github.com/swagger-api/swagger-ui.git`
2. `cd swagger-ui`
3. `npm run dev`
4. Wait a bit
5. Open http://localhost:3200/

In order to use my swagger.yaml.first create a new copy of the swagger.yaml file in the devhelpers folder

file go to devhelpers/dev-helper-initializer.js and replace
```
url: "https://petstore.swagger.io/v2/swagger.json",
```

with
```
url: "./swagger.yaml",
```
