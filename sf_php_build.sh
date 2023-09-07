PHP_VERSION=$1
PHP_AGENT_DIR=$(pwd)
EXTENSION_DIR="${PHP_AGENT_DIR}/extensions"
EXTENSION_CFG_DIR="${PHP_AGENT_DIR}/etc"
BOOTSTRAP_FILE_PATH="${PHP_AGENT_DIR}/agent/php/bootstrap_php_part.php"
BACKUP_EXTENSION=".agent.bck"


rm -rf ${EXTENSION_CFG_DIR}
rm -rf ${EXTENSION_DIR}

mkdir -p ${EXTENSION_CFG_DIR}
mkdir -p ${EXTENSION_DIR}

cp -rf agent/native/ext/modules/* ${EXTENSION_DIR}

mkdir output
filename="sf-apm-php-agent-${PHP_VERSION}.tar.gz"


tar cvzf output/${filename} ./agent/php/*