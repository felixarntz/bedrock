#!/usr/bin/env bash
# Provision WordPress Stable

DOMAIN=`get_primary_host "${VVV_SITE_NAME}".dev`
DOMAINS=`get_hosts "${DOMAIN}"`
SITE_TITLE=`get_config_value 'site_title' "${DOMAIN}"`
WP_TYPE=`get_config_value 'wp_type' "single"`
DB_NAME=`get_config_value 'db_name' "${VVV_SITE_NAME}"`
DB_NAME=${DB_NAME//[\\\/\.\<\>\:\"\'\|\?\!\*]/}

# Make a database, if we don't already have one
echo -e "\nCreating database '${DB_NAME}' (if it's not already there)"
mysql -u root --password=root -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME}"
mysql -u root --password=root -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO wp@localhost IDENTIFIED BY 'wp';"
echo -e "\n DB operations done.\n\n"

# Nginx Logs
mkdir -p ${VVV_PATH_TO_SITE}/log
touch ${VVV_PATH_TO_SITE}/log/error.log
touch ${VVV_PATH_TO_SITE}/log/access.log

# Install and configure the latest stable version of WordPress
if [[ ! -d "${VVV_PATH_TO_SITE}/web/core/wp-load.php" ]]; then
	echo "Downloading WordPress and dependencies..."
	noroot composer install

	echo "Setting up .env file..."
	cp -f "${VVV_PATH_TO_SITE}/provision/.env.tmpl" "${VVV_PATH_TO_SITE}/.env"
	sed -i "s#{{DB_NAME_HERE}}#${DB_NAME}#" "${VVV_PATH_TO_SITE}/.env"

	echo "Installing WordPress..."

	if [ "${WP_TYPE}" = "subdomain" ]; then
		INSTALL_COMMAND="multisite-install --subdomains"
	elif [ "${WP_TYPE}" = "subdirectory" ]; then
		INSTALL_COMMAND="multisite-install"
	else
		INSTALL_COMMAND="install"
	fi

	noroot wp core ${INSTALL_COMMAND} --url="${DOMAIN}" --quiet --title="${SITE_TITLE}" --admin_name=admin --admin_email="admin@local.dev" --admin_password="password"

	noroot wp comment delete 1 --force --quiet
	noroot wp post delete 1 --force --quiet
else
	echo "Updating WordPress and dependencies..."
	noroot composer update
fi

cp -f "${VVV_PATH_TO_SITE}/provision/vvv-nginx.conf.tmpl" "${VVV_PATH_TO_SITE}/provision/vvv-nginx.conf"
sed -i "s#{{DOMAINS_HERE}}#${DOMAINS}#" "${VVV_PATH_TO_SITE}/provision/vvv-nginx.conf"
