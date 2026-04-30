## Cloning this repository

<br><br>

#### Requirements

1. PHP 8.2^ version (Laravel 11)
2. Node JS
3. If you're using vscode, then you might need this extension: `Vue.volar`
4. I used this vscode extenstion to test my API: `humao.rest-client` [More about this extension](https://github.com/Huachao/vscode-restclient)

<br>



```
git clone https://github.com/johndcjustme/cdrrmo.git
cd cdrrmo

composer update

npm install
```

Copy **.env.example** and paste it as **.env** in the same directory.
Open **.env** file and update the database connection

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cdrrmo_db
DB_USERNAME=root
DB_PASSWORD=
```

```
php artisan key:generate
php artisan migrate
php artisan db:seed
```

```
npm run dev
php artisan serve
```

Then, your server should be running on this port or something like this http://localhost:8000/

<br>



<br>

Login details:

username: `admin@admin.com`
password: `password`

<br>

**Done!**

<br><br><br>

## WebView Routes
* localhost:8000/news/{id}
* localhost:8000/safety-tips/{id}
* localhost:8000/emergency-preparedness/{id}