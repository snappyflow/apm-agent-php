PHP_AGENT_DIR=$(pwd)
EXTENSION_DIR="${PHP_AGENT_DIR}/extensions"
EXTENSION_CFG_DIR="${PHP_AGENT_DIR}/etc"
BOOTSTRAP_FILE_PATH="${PHP_AGENT_DIR}/src/bootstrap_php_part.php"
BACKUP_EXTENSION=".agent.bck"


rm -rf ${EXTENSION_CFG_DIR}
rm -rf ${EXTENSION_DIR}

mkdir -p ${EXTENSION_CFG_DIR}
mkdir -p ${EXTENSION_DIR}

cp -rf src/ext/modules/* ${EXTENSION_DIR}

mkdir output
filename="sf-apm-php-agent-7.0.tar.gz"


tar cvzf output/${filename} ./src/*