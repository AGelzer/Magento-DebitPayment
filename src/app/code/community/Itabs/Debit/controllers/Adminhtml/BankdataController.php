<?php

class Itabs_Debit_Adminhtml_BankdataController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return Itabs_Debit_Adminhtml_BankdataController
     */
    protected function _initLayout()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/convert/debitpayment')
            ->_addBreadcrumb(
                $this->_getHelper()->__('System'),
                $this->_getHelper()->__('System')
            )
            ->_addBreadcrumb(
                $this->_getHelper()->__('Import/Export'),
                $this->_getHelper()->__('Import/Export')
            )
            ->_addBreadcrumb(
                $this->_getDebitHelper()->__('Bank Data'),
                $this->_getDebitHelper()->__('Bank Data')
            )
            ->_title($this->_getHelper()->__('System'))
            ->_title($this->_getHelper()->__('Import/Export'))
            ->_title($this->_getDebitHelper()->__('Bank Data'));

        return $this;
    }

    /**
     *
     */
    public function indexAction()
    {
        $this->_initLayout();
        $this->renderLayout();
    }

    /**
     *
     */
    public function gridAction()
    {
        $block = $this->getLayout()->createBlock('debit/adminhtml_bankdata_grid')->toHtml();
        $this->getResponse()->setBody($block);
    }

    /**
     *
     */
    public function uploadAction()
    {
        $this->_initLayout();
        $this->renderLayout();
    }

    /**
     *
     */
    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $country = $this->getRequest()->getPost('country_id', false);
            if (!$country || !isset($_FILES['upload_file']['name']) || !file_exists($_FILES['upload_file']['tmp_name'])) {
                $this->_getSession()->addError($this->_getDebitHelper()->__('Please fill in all required fields.'));
                $this->_redirect('*/*/upload');
                return;
            }


            try {
                $path = Mage::getBaseDir('var') . DS;
                $filename = 'debitpayment_upload_file.csv';

                $uploader = new Varien_File_Uploader('upload_file');
                $uploader->setAllowedExtensions(array('csv'));
                $uploader->setAllowCreateFolders(true);
                $uploader->setAllowRenameFiles(false);
                $uploader->setFilesDispersion(false);
                $uploader->save($path, $filename);

                $file = new Varien_Io_File();
                $file->open(array('path' => $path));
                $file->streamOpen($filename, 'r');

                $i = 1;
                $import = array();
                while (($line = $file->streamReadCsv()) !== false) {
                    if ($i == 1) {
                        $i++;
                        continue;
                    }

                    // Check if routing number already exists
                    $routingNumber = trim($line[0]);
                    if (array_key_exists($routingNumber, $import)) {
                        continue;
                    }

                    // Add bank to array
                    $import[$routingNumber] = array(
                        'routing_number' => $routingNumber,
                        'swift_code' => trim($line[2]),
                        'bank_name' => utf8_encode(trim($line[1]))
                    );
                }

                // Delete all banks by the given country_id
                /* @var $model Itabs_Debit_Model_Bankdata */
                $model = Mage::getModel('debit/bankdata');
                $model->deleteByCountryId($country);

                foreach ($import as $data) {
                    /* @var $model Itabs_Debit_Model_Bankdata */
                    $model = Mage::getModel('debit/bankdata');
                    $model->addData($data);
                    $model->setData('country_id', $country);
                    $model->save();
                }

                unlink($path.$filename);
                $this->_getSession()->addSuccess($this->_getDebitHelper()->__('Upload successful!'));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/upload');
                return;
            }
        }

        $this->_redirect('*/*');
    }

    /**
     * Retrieve the helper class
     *
     * @return Itabs_Debit_Helper_Adminhtml Helper
     */
    protected function _getDebitHelper()
    {
        return Mage::helper('debit/adminhtml');
    }
}
