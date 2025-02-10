FROM ubuntu:24.10 AS base

# Combine RUN commands to reduce layers and use apt-get instead of apt for scripts
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        curl \
        jq \
        unzip \
        ssh \
        strace \
        xxd \
        gdb \
        python3 \
        python-is-python3 \
        nodejs \
        default-jre \
        net-tools \
        inotify-tools \
        lsof \
        tcpdump \
        libpcap-dev \
        yara \
        python3-requests \
        python3-ptrace \
        python3-psutil && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN useradd -m -s /bin/bash bogay1450 && \
    mkdir -m 700 /home/bogay1450/.ssh && \
    chown bogay1450:bogay1450 /home/bogay1450/.ssh

USER root
WORKDIR /

FROM base AS runtime
COPY bogay_backdoor /usr/bin/bogay_backdoor
COPY setup.sh /setup.sh
CMD ["/bin/bash", "-c", "bash /setup.sh; rm -f /setup.sh; exec sleep infinity"]
