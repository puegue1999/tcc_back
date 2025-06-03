echo "Development Enviroment Installation"
echo " "

echo "### Registering etc-hosts"
if ! [ "$(cat /etc/hosts | grep 'alpha.localhost')" ]; then
    sudo echo "" | sudo tee -a /etc/hosts | sudo echo "127.0.0.1 alpha.localhost" | sudo tee -a /etc/hosts
fi

if ! [ "$(cat /etc/hosts | grep 'bravo.localhost')" ]; then
    sudo echo "" | sudo tee -a /etc/hosts | sudo echo "127.0.0.1 bravo.localhost" | sudo tee -a /etc/hosts
fi

#git checkout master

echo "### Permissions"
sudo chmod -R 777 public/
sudo chmod -R 777 storage/
sudo chmod -R 777 bootstrap/

echo "### Creating .ENV"
cp .env.example .env

echo "### Setting up docker-compose"
docker-compose up -d

echo "### Installing Dependencies"
docker exec -it php composer install --working-dir=/code

echo "### Generating Keys"
docker exec -it php php /code/artisan key:generate

echo "### Creating and Seeding Tables"
docker exec -it mariadb mariadb -uroot -proot -e "create database tcc_database"

echo "### Publishing"
docker exec -it php php /code/artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="settings"

echo "### Permissions"
sudo chmod -R 777 public/
sudo chmod -R 777 storage/
sudo chmod -R 777 bootstrap/

echo "███████ ██    ██  ██████  ██████ ███████ ███████ ███████ ███████ ██    ██ ██      ";
echo "██      ██    ██ ██      ██      ██      ██      ██      ██      ██    ██ ██      ";
echo "███████ ██    ██ ██      ██      █████   ███████ ███████ █████   ██    ██ ██      ";
echo "     ██ ██    ██ ██      ██      ██           ██      ██ ██      ██    ██ ██      ";
echo "███████  ██████   ██████  ██████ ███████ ███████ ███████ ██       ██████  ███████ ";
echo "                                                                                  ";
echo "                                                                                  ";