set -ex

WP_CLI_BIN_DIR=/tmp/wp-cli-phar

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

install_wp_cli() {

	# the test suite will pick up the executable found in $WP_CLI_BIN_DIR
	mkdir -p $WP_CLI_BIN_DIR
	download https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar $WP_CLI_BIN_DIR/wp
	chmod +x $WP_CLI_BIN_DIR/wp

}

install_db() {
	mysql -e 'CREATE DATABASE IF NOT EXISTS wordpress-test;' -uroot
	mysql -e 'GRANT ALL PRIVILEGES ON wordpress-test.* TO "wordpress"@"localhost" IDENTIFIED BY "wordpress"' -uroot
}

install_wp_cli
install_db
alias wp='$WP_CLI_BIN_DIR/wp'
