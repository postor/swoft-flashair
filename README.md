# swoft 尝试 - 从 flashair 备份文件

会用到

- websocket 与页面实时通信
- composer 安装第三方包

## 环境准备

创建项目

```
composer create-project swoft/swoft swoft-flashair
```

因为windows不支持swoole，依赖安装会因为没有swoole插件而报错，所以需要增加忽略参数

```
composer install --ignore-platform-reqs
```

启动借助于docker 

> 因为windows目录映射总是有问题，所以使用build的方式，如果目录映射没问题还是使用swoft官方镜像比较好，不用每次composer安装
>
> 如遇到同样问题可参考本仓库修改以下文件：Dockerfile、.dockerignore、docker-compose.yml

```
docker-compose build 
docker-compose up --force-recreate
```

测试运行
