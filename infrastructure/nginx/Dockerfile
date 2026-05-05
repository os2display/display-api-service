ARG APP_VERSION="develop"
ARG APP_IMAGE="ghcr.io/os2display/display-api-service"

# Pull the API image that was built and pushed in the preceding step.
# Multi-arch manifests resolve per platform, so the arm64 nginx build
# copies arm64 contents and amd64 copies amd64 — no QEMU re-execution
# of npm/composer for the second image.
FROM ${APP_IMAGE}:${APP_VERSION} AS app


######## Nginx production image ########
FROM nginxinc/nginx-unprivileged:alpine
LABEL maintainer="ITK Dev <itkdev@ba.aarhus.dk>"

ARG UID=101

# Defaults for the envsubst pass over /etc/nginx/templates/default.conf.template
# at container start; every value below is referenced from that template.
ENV NGINX_PORT=8080 \
    NGINX_FPM_SERVICE=os2display \
    NGINX_FPM_PORT=9000 \
    NGINX_MAX_BODY_SIZE=140m \
    NGINX_SET_REAL_IP_FROM=172.16.0.0/12 \
    NGINX_WEB_ROOT=/app/public

WORKDIR /app

# Single source of truth: public/ (incl. build/ and release.json) comes
# from the just-published API image, not a duplicated builder stage.
COPY --from=app --chown=$UID:0 --chmod=775 /app/public ./public

# Copy nginx configuration
COPY --chown=$UID:0 etc /etc/nginx

# Add health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:${NGINX_PORT}/health || exit 1

USER $UID

EXPOSE ${NGINX_PORT}

CMD ["nginx", "-g", "daemon off;"]
