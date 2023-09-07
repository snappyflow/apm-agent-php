# cd src/ext
phpize
CFLAGS="-std=gnu99" ./configure --enable-elastic_apm
make clean
make
# sudo make install