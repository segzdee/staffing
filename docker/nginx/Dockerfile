FROM nginx

# Set permissions
ARG UID

ENV UID=${UID}

RUN usermod -u ${UID} nginx
