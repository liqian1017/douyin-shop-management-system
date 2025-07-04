# 抖音店铺登记管理系统

这是一个用于抖音店铺登记和管理的系统，提供了法人信息管理、营业执照管理、店铺管理、资金提现、抖音号登记等功能。系统支持数据导入导出，方便批量操作，提高工作效率。

## 系统功能

1. **用户管理**：支持管理员、经理和普通用户三种角色，不同角色有不同的操作权限
2. **法人信息管理**：管理店铺法人的基本信息
3. **营业执照管理**：记录和管理营业执照信息，包括可开店数量限制
4. **店铺管理**：管理抖音店铺信息，包括店铺ID、法人信息、资金状况等
5. **资金提现管理**：记录和管理店铺的提现申请和操作
6. **抖音号登记**：管理与店铺相关的抖音号信息
7. **数据导入导出**：支持批量导入导出各类数据，方便数据迁移和备份
8. **系统工具**：提供系统信息查看、管理员密码重置等功能

## 系统架构

系统采用经典的MVC架构，主要包含以下部分：

- **控制器(Controller)**：负责处理请求和业务逻辑
- **模型(Model)**：负责数据处理和数据库操作
- **视图(View)**：负责页面展示
- **路由(Router)**：负责URL分发
- **API接口**：提供数据导入导出等功能

## 技术栈

- **后端**：PHP
- **前端**：HTML, CSS, JavaScript
- **数据库**：MySQL
- **模板引擎**：自定义PHP模板

## 安装与配置

1. 将项目文件上传到Web服务器
2. 创建MySQL数据库
3. 访问install.php进行安装，按照提示配置数据库连接信息
4. 安装完成后，使用默认管理员账户登录：
   - 用户名：admin
   - 密码：admin123
5. 登录后建议立即修改管理员密码

## 使用说明

1. **登录系统**：使用管理员或其他授权账户登录
2. **导航菜单**：通过左侧导航栏选择不同的管理模块
3. **数据管理**：在各管理页面可以查看、添加、编辑和删除相关数据
4. **数据导入导出**：在工具页面可以进行数据的导入和导出操作
5. **用户管理**：管理员可以管理系统用户，包括添加、编辑和删除用户

## 目录结构

```
wwwroot/
├── app/                # 应用核心文件
│   ├── Auth.php        # 认证类
│   ├── Controller.php  # 控制器
│   ├── Model.php       # 模型
│   └── Router.php      # 路由
├── api/                # API接口
│   ├── export.php      # 数据导出
│   └── import.php      # 数据导入
├── assets/             # 静态资源
│   └── style.css       # 样式文件
├── config.php          # 配置文件
├── index.php           # 入口文件
├── install.php         # 安装文件
└── views/              # 视图文件
    ├── dashboard.php   # 仪表盘
    ├── login.php       # 登录页面
    ├── manage.php      # 管理页面
    └── tools.php       # 工具页面
```

## 注意事项

1. 系统默认使用UTF-8编码，确保数据库和Web服务器也使用相同的编码
2. 生产环境建议关闭DEBUG_MODE以提高安全性
3. 定期备份数据库，防止数据丢失
4. 为确保系统安全，建议定期更新管理员密码
5. 导入导出功能支持CSV格式文件，确保文件格式正确

## 常见问题

1. **登录失败**：检查用户名和密码是否正确，注意大小写
2. **数据导入失败**：确保CSV文件格式正确，包含必要的字段
3. **页面显示异常**：清除浏览器缓存或尝试使用其他浏览器
4. **数据库连接问题**：检查config.php中的数据库配置是否正确

如果遇到其他问题，请联系系统管理员或技术支持人员。
