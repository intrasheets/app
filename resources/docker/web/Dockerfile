FROM nginx:latest

ENV container docker

COPY default.conf /etc/nginx/conf.d

EXPOSE 80
EXPOSE 443

STOPSIGNAL SIGTERM

CMD ["/usr/sbin/nginx", "-g", "daemon off;"]
