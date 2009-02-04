        # run some programs
        '''
        // create table, let it be as follows
        QTableWidget *table = new QTableWidget;
        connect(table, SIGNAL(customContextMenuRequested ( const QPoint &)), this, SLOT(popupYourMenu(const QPoint &)));

        void YourClass:popupYourMenu(const QPoint & pos)
        {
        // create popupMenu as QMenu object
        // populate it

        popupMenu->popup(pos);
        }
        '''

 def contextMenuEvent(self, event):
        menu = QMenu(self)
        oneAction = menu.addAction("&One")
        twoAction = menu.addAction("&Two")
        self.connect(oneAction, SIGNAL("triggered()"), self.one)
        self.connect(twoAction, SIGNAL("triggered()"), self.two)
        menu.exec_(event.globalPos())

    def one(self):
        self.message = QString("Menu option One")
        print "Menu option One"
        #self.update()

    def two(self):
        self.message = QString("Menu option Two")
        print "Menu option Two"
        #self.update()

#    def three(self):
#        self.message = QString("Menu option Three")
#        print "Menu option Three"
#        self.update()

#    def event(self, event):
#        if event.type() == QEvent.KeyPress and \
#           event.key() == Qt.Key_Tab:
#            self.key = QString("Tab captured in event()")
#            print "Captured tab"
#            self.update()
#            return True
#        return QWidget.event(self, event)
