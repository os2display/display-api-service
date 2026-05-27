ARG APP_VERSION="develop"
ARG APP_IMAGE="ghcr.io/os2display/display-api-service"

# Pull the API image that was built and pushed in the preceding step.
# Multi-arch manifests resolve per platform, so the arm64 nginx build
# copies arm64 contents and amd64 copies amd64 — no QEMU re-execution
# of npm/composer for the second image.
FROM ${APP_IMAGE}:${APP_VERSION} AS app


######## Nginx production image ########
FROM nginxinc/nginx-unprivileged:alpine

# OCI image annotations. `title`, `description`, `url`, `source`, `version`,
# `created`, `revision` and `licenses` are injected at build time by the
# docker/metadata-action step in .github/workflows/build-images.yml — the
# nginx-specific `title`/`description` overrides live in that workflow
# because metadata-action's --label flags win over Dockerfile LABELs.
LABEL org.opencontainers.image.authors="ITK Dev <itkdev@ba.aarhus.dk>" \
      org.opencontainers.image.vendor="OS2Display" \
      org.opencontainers.image.documentation="https://github.com/os2display/display-api-service#readme" \
      org.opencontainers.image.base.name="nginxinc/nginx-unprivileged:alpine"

ARG UID=101

# Defaults for the envsubst pass over /etc/nginx/templates/default.conf.template
# at container start; every value below is referenced from that template.
ENV NGINX_PORT=8080 \
    NGINX_FPM_SERVICE=os2display \
    NGINX_FPM_PORT=9000 \
    NGINX_MAX_BODY_SIZE=200m \
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
