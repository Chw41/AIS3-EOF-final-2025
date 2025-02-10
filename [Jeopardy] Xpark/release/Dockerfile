FROM ubuntu:22.04
MAINTAINER YingMuo

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev xinetd &&\
    rm -rf /var/lib/apt/lists/*

RUN useradd -m chal
RUN chown -R root:root /home/chal
RUN chmod -R 755 /home/chal

CMD ["/usr/sbin/xinetd", "-dontfork"]
