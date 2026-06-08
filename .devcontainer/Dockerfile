ARG VARIANT=1-8.4
FROM mcr.microsoft.com/devcontainers/php:${VARIANT}

RUN curl --proto '=https' --tlsv1.2 -sSf https://carthage.software/mago.sh \
    | bash -s -- --install-dir=/usr/local/bin
