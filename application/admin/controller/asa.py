#!/usr/bin/env python
# -*- coding:utf-8 -*-
from selenium import webdriver
import time
from time import sleep # this should go at the top of the file
import os,base64 
import sys
#import logging
import requests
import json


from selenium.webdriver.chrome.options import Options
import selenium.webdriver.support.ui as ui
from selenium.webdriver.common.keys import Keys



fpdm = sys.argv[1]
fpdmna = sys.argv[2]

ret = {'code':1,'data':''}
chrome_options = Options()

chrome_options.add_argument('--no-sandbox') #让Chrome在root权限运行

chrome_options.add_argument('--disable-dev-shm-usage') #不打开图形界面

chrome_options.add_argument('--headless') #浏览器不提供可视化页面
#if ippro:
	#chrome_options.add_argument("--proxy-server=http://58.253.177.165:45122") #设置代理

chrome_options.add_argument('--ignore-ssl-errors=yes')
chrome_options.add_argument('--ignore-certificate-errors')
chrome_options.add_experimental_option('excludeSwitches', ['enable-automation'])
chrome_options.add_argument("--disable-blink-features")
chrome_options.add_argument("--disable-blink-features=AutomationControlled")
#chrome_options.add_argument("---widows-size==2220,1500")
chrome_options.add_argument("---widows-size==1220,800")
#chrome_options.add_argument('blink-settings=imagesEnabled=false') #不加载图片, 提升速度

chrome_options.add_argument('--disable-gpu') #谷歌文档提到需要加上这个属性来规避bug



driver = webdriver.Chrome(options=chrome_options,executable_path='/home/wwwroot/caiji/fapiao/html/chromedriver') #Chrome驱动的位置，此学习记录中安装到了Chrome程序根目录，该路径为绝对路径



driver.execute_cdp_cmd("Page.addScriptToEvaluateOnNewDocument", {
  "source": """
    Object.defineProperty(navigator, 'webdriver', {
      get: () => false
    })
  """
})



#logging.basicConfig(level = logging.DEBUG)
driver.set_window_size(1500,1900)
driver.maximize_window()
url = 'http://asaapi.appgodlike.com/ads/getcampaigns/?isshow=1&uuid='+fpdm
#url = 'file:///home/fapiao.html'
driver.get(url) # 获取

driver.save_screenshot(r"/home/wwwroot/kezi/public/uploads/file/jp"+fpdm+fpdmna+".png")
#print(ret)
driver.quit()
exit()



