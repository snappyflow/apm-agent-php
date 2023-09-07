#!/usr/bin/env bash

######### Let's support alpine installations
PATH=${PATH}:/usr/local/bin

################################################################################
############################ GLOBAL VARIABLES ##################################
################################################################################
PHP_AGENT_DIR=/opt/elastic/apm-agent-php
EXTENSION_DIR="${PHP_AGENT_DIR}/extensions"
EXTENSION_CFG_DIR="${PHP_AGENT_DIR}/etc"
BOOTSTRAP_FILE_PATH="${PHP_AGENT_DIR}/src/bootstrap_php_part.php"
BACKUP_EXTENSION=".agent.bck"
ELASTIC_INI_FILE_NAME="elastic-apm.ini"
CUSTOM_INI_FILE_NAME="elastic-apm-custom.ini"

################################################################################
########################## FUNCTION CALLS BELOW ################################
################################################################################

################################################################################
#### Function php_command ######################################################
function php_command() {
    PHP_BIN=$(command -v php)
    ${PHP_BIN} -d memory_limit=128M "$@"
}

################################################################################
#### Function php_ini_file_path ################################################
function php_ini_file_path() {
    php_command -i \
        | grep 'Configuration File (php.ini) Path =>' \
        | sed -e 's#Configuration File (php.ini) Path =>##g' \
        | head -n 1 \
        | awk '{print $1}'
}

################################################################################
#### Function php_api ##########################################################
function php_api() {
    php -i \
        | grep 'PHP API' \
        | sed -e 's#.* =>##g' \
        | awk '{print $1}'
}

################################################################################
#### Function php_config_d_path ################################################
function php_config_d_path() {
    php_command -i \
        | grep 'Scan this dir for additional .ini files =>' \
        | sed -e 's#Scan this dir for additional .ini files =>##g' \
        | head -n 1 \
        | awk '{print $1}'
}

################################################################################
#### Function is_extension_enabled #############################################
function is_extension_enabled() {
    php_command -m | grep -q 'elastic'
}

################################################################################
#### Function install_conf_d_files #############################################
function install_conf_d_files() {
    PHP_CONFIG_D_PATH=$1
    INI_FILE_PATH="${EXTENSION_CFG_DIR}/$ELASTIC_INI_FILE_NAME"
    CUSTOM_INI_FILE_PATH="${EXTENSION_CFG_DIR}/${CUSTOM_INI_FILE_NAME}"

    generate_configuration_files "${INI_FILE_PATH}" "${CUSTOM_INI_FILE_PATH}"

    echo "Configuring ${ELASTIC_INI_FILE_NAME} for supported SAPI's"

    # Detect installed SAPI's
    SAPI_DIR=${PHP_CONFIG_D_PATH%/*/conf.d}/
    SAPI_CONFIG_DIRS=()
    if [ "${PHP_CONFIG_D_PATH}" != "${SAPI_DIR}" ]; then
        # CLI
        CLI_CONF_D_PATH="${SAPI_DIR}cli/conf.d"
        if [ -d "${CLI_CONF_D_PATH}" ]; then
            SAPI_CONFIG_DIRS+=("${CLI_CONF_D_PATH}")
        fi
        # Apache
        APACHE_CONF_D_PATH="${SAPI_DIR}apache2/conf.d"
        if [ -d "${APACHE_CONF_D_PATH}" ]; then
            SAPI_CONFIG_DIRS+=("${APACHE_CONF_D_PATH}")
        fi
        ## FPM
        FPM_CONF_D_PATH="${SAPI_DIR}fpm/conf.d"
        if [ -d "${FPM_CONF_D_PATH}" ]; then
            SAPI_CONFIG_DIRS+=("${FPM_CONF_D_PATH}")
        fi
    fi

    if [ ${#SAPI_CONFIG_DIRS[@]} -eq 0 ]; then
        SAPI_CONFIG_DIRS+=("$PHP_CONFIG_D_PATH")
    fi

    for SAPI_CONFIG_D_PATH in "${SAPI_CONFIG_DIRS[@]}" ; do
        echo "Found SAPI config directory: ${SAPI_CONFIG_D_PATH}"
        link_file "${INI_FILE_PATH}" "${SAPI_CONFIG_D_PATH}/98-${ELASTIC_INI_FILE_NAME}"
        link_file "${CUSTOM_INI_FILE_PATH}" "${SAPI_CONFIG_D_PATH}/99-${CUSTOM_INI_FILE_NAME}"
    done
}

################################################################################
#### Function generate_configuration_files #####################################
function generate_configuration_files() {
    INI_FILE_PATH="${1}"
    CUSTOM_INI_FILE_PATH="${2}"

    ## IMPORTANT: This file will be always override if already exists for a
    ##            previous installation.
    echo "Creating ${INI_FILE_PATH}"
    CONTENT=$(add_extension_configuration)
    tee "${INI_FILE_PATH}" <<EOF
; ***** DO NOT EDIT THIS FILE *****
; THIS IS AN AUTO-GENERATED FILE by the Elastic PHP agent post-install.sh script
; To overwrite the INI settings for this extension, edit
; the INI file in this directory "${CUSTOM_INI_FILE_PATH}"
[elastic]
${CONTENT}
; END OF AUTO-GENERATED by the Elastic PHP agent post-install.sh script
EOF

    echo "${INI_FILE_PATH} created"

    if [ ! -f "${CUSTOM_INI_FILE_PATH}" ]; then
        touch "${CUSTOM_INI_FILE_PATH}"
        echo "Created empty ${CUSTOM_INI_FILE_PATH}"
    fi
}

################################################################################
#### Function link_file ########################################################
function link_file() {
    echo "Linking ${1} to ${2}"
    test -f "${2}" && rm "${2}"
    ln -s "${1}" "${2}"
}

################################################################################
#### Function add_extension_configuration_to_file ##############################
function add_extension_configuration_to_file() {
    CONTENT=$(add_extension_configuration)
    ## IMPORTANT: The below content is also used in the before-uninstall.sh
    ##            script.
    tee -a "$1" <<EOF
; THIS IS AN AUTO-GENERATED FILE by the Elastic PHP agent post-install.sh script
${CONTENT}
; END OF AUTO-GENERATED by the Elastic PHP agent post-install.sh script
EOF
}

################################################################################
#### Function add_extension_configuration ######################################
function add_extension_configuration() {
    cat <<EOF
extension=${EXTENSION_FILE_PATH}
elastic_apm.bootstrap_php_part_file=${BOOTSTRAP_FILE_PATH}
EOF
}

################################################################################
#### Function manual_extension_agent_setup #####################################
function manual_extension_agent_setup() {
    echo 'Set up the Agent manually as explained in:'
    echo 'https://github.com/elastic/apm-agent-php/blob/main/docs/setup.asciidoc'
    if [ -e "${EXTENSION_FILE_PATH}" ] ; then
        echo 'Enable the extension by adding the following to your php.ini file:'
        echo "extension=${EXTENSION_FILE_PATH}"
        echo "elastic_apm.bootstrap_php_part_file=${BOOTSTRAP_FILE_PATH}"
    fi
}

################################################################################
#### Function agent_extension_not_supported ####################################
function agent_extension_not_supported() {
    PHP_API=$(php_api)
    echo 'Failed. Elastic PHP agent extension not supported for the current PHP API version.'
    echo "    PHP API => ${PHP_API}"
}

################################################################################
#### Function get_extension_file ###############################################
function get_extension_file() {
    PHP_API=$(php_api)
    ## If alpine then add another suffix
#    if grep -q -i alpine /etc/os-release; then
#        SUFFIX=-alpine
#    fi
    echo "${EXTENSION_DIR}/elastic_apm-${PHP_API}${SUFFIX}.so"
}

################################################################################
#### Function is_php_supported #################################################
function is_php_supported() {
    PHP_MAJOR_MINOR=$(php_command -r 'echo PHP_MAJOR_VERSION;').$(php_command -r 'echo PHP_MINOR_VERSION;')
    echo "Detected PHP version '${PHP_MAJOR_MINOR}'"
    # Make sure list of PHP versions supported by the Elastic APM PHP Agent is in sync.
    # See the comment in .ci/shared.sh
    if  [ "${PHP_MAJOR_MINOR}" == "7.2" ] || \
        [ "${PHP_MAJOR_MINOR}" == "7.3" ] || \
        [ "${PHP_MAJOR_MINOR}" == "7.4" ] || \
        [ "${PHP_MAJOR_MINOR}" == "8.0" ] || \
        [ "${PHP_MAJOR_MINOR}" == "8.1" ] || \
        [ "${PHP_MAJOR_MINOR}" == "8.2" ]
    then
        return 0
    else
        echo 'Failed. The supported PHP versions are 7.2-8.2.'
        return 1
    fi
}

################################################################################
############################### MAIN ###########################################
################################################################################
echo 'Installing Elastic PHP agent'
EXTENSION_FILE_PATH=$(get_extension_file)
PHP_INI_FILE_PATH="$(php_ini_file_path)/php.ini"
PHP_CONFIG_D_PATH="$(php_config_d_path)"

echo "DEBUG: after-install parameter is '$1'"

if ! is_php_supported ; then
    echo 'Failed. Elastic PHP agent extension is not supported for the existing PHP installation.'
    exit 1
fi

if [ -e "${PHP_CONFIG_D_PATH}" ]; then
    install_conf_d_files "${PHP_CONFIG_D_PATH}"
else
    if [ -e "${PHP_INI_FILE_PATH}" ] ; then
        if [ -e "${EXTENSION_FILE_PATH}" ] ; then
            if grep -q "${EXTENSION_FILE_PATH}" "${PHP_INI_FILE_PATH}" ; then
                echo '  extension configuration already exists for the Elastic PHP agent.'
                echo '  skipping ... '
            else
                echo "${PHP_INI_FILE_PATH} has been configured with the Elastic PHP agent setup."
                cp -fa "${PHP_INI_FILE_PATH}" "${PHP_INI_FILE_PATH}${BACKUP_EXTENSION}"
                add_extension_configuration_to_file "${PHP_INI_FILE_PATH}"
            fi
        else
            agent_extension_not_supported
        fi
    else
        if [ -e "${EXTENSION_FILE_PATH}" ] ; then
            echo "${PHP_INI_FILE_PATH} has been created with the Elastic PHP agent setup."
            add_extension_configuration_to_file "${PHP_INI_FILE_PATH}"
        else
            agent_extension_not_supported
        fi
    fi
fi

if is_extension_enabled ; then
    echo 'Extension enabled successfully for Elastic PHP agent'
else
    echo 'Failed. Elastic PHP agent extension is not enabled'
    if [ -e "${PHP_INI_FILE_PATH}${BACKUP_EXTENSION}" ] ; then
        echo "Reverted changes in the file ${PHP_INI_FILE_PATH}"
        mv -f "${PHP_INI_FILE_PATH}${BACKUP_EXTENSION}" "${PHP_INI_FILE_PATH}"
    fi
    manual_extension_agent_setup
fi
