.PHONY: help build appstore clean

app_name=done

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version+=main
composer=$(shell which composer 2> /dev/null)

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
composer:
ifeq (, $(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
	php $(build_tools_directory)/composer.phar update --prefer-dist
else
	composer install --prefer-dist
	composer update --prefer-dist
endif


help:
	@echo "Available targets:"
	@echo "  make build     - Install dependencies"
	@echo "  make appstore  - Build release archive for App Store"
	@echo "  make clean     - Clean build artifacts"

# Install dependencies
build:
	composer install --no-dev
	composer dump-autoload -o
	npm ci
	npm run build

build-js-production:
	@export NVM_DIR="$$HOME/.nvm"; \
	[ -s "$$NVM_DIR/nvm.sh" ] && . "$$NVM_DIR/nvm.sh"; \
	nvm use 20; \
	npm run build

# Dev env management
dev-setup: clean npm-init

# Check and setup Node.js version
check-node:
	@echo "Checking Node.js version..."
	@if [ ! -d "$$HOME/.nvm" ]; then \
		echo "Installing nvm..."; \
		curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash; \
	fi
	@export NVM_DIR="$$HOME/.nvm"; \
	[ -s "$$NVM_DIR/nvm.sh" ] && . "$$NVM_DIR/nvm.sh"; \
	if ! nvm version 20 >/dev/null 2>&1; then \
		echo "Installing Node.js 20..."; \
		nvm install 20; \
	fi; \
	nvm use 20

npm-init: check-node
	@export NVM_DIR="$$HOME/.nvm"; \
	[ -s "$$NVM_DIR/nvm.sh" ] && . "$$NVM_DIR/nvm.sh"; \
	nvm use 20; \
	if [ -d node_modules ]; then \
		mkdir -p .empty-dir; \
		rsync -a --delete .empty-dir/ node_modules/ || true; \
		rmdir .empty-dir || true; \
		rm -rf node_modules || true; \
	fi; \
	rm -f package-lock.json; \
	npm install

# Build release archive for Nextcloud App Store
appstore: dev-setup build-js-production
	mkdir -p $(sign_dir)
	# mkdir -p build
	# Install production dependencies
	# composer install --no-dev --optimize-autoloader
	# Build frontend
	# npm ci
	# npm run build
	# Create archive
	rsync -a \
		--exclude=/.git \
		--exclude=/.github \
		--exclude=/build \
		--exclude=/node_modules \
		--exclude=/tests \
		--exclude=/vendor \
		--exclude=/vendor-bin \
		--exclude=/.gitignore \
		--exclude=/.php-cs-fixer.cache \
		--exclude=/Makefile \
		--exclude=/webpack.js \
		--exclude=/package.json \
		--exclude=/package-lock.json \
		--exclude=/composer.json \
		--exclude=/composer.lock \
		--exclude=/psalm.xml \
		--exclude=/stylelint.config.js \
		--exclude=/src \
		. build/done
	rsync -a build/done/ $(sign_dir)/$(app_name)/
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing app files…"; \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(sign_dir)/$(app_name); \
	fi
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name).tar.gz | openssl base64; \
	fi

# Clean build artifacts
clean:
	rm -rf build

