import React, { useCallback, useEffect, useState } from 'react'
import { formatAmount, Label, usePluginInfo } from '@webstollen/react-jtl-plugin/lib'
import useApi from '@webstollen/react-jtl-plugin/lib/hooks/useAPI'
import OrderDetails from './OrderDetails'
import useErrorSnack from '../../hooks/useErrorSnack'
import DataTable, { DataTableHeader } from '@webstollen/react-jtl-plugin/lib/components/DataTable/DataTable'
import useOrders from '../../hooks/useOrders'
import TextLink from '@webstollen/react-jtl-plugin/lib/components/TextLink'
import { jtlStatus2label, molliePaymentStatusLabel, PaymentMethod2img } from '../../helper'
import Button from '@webstollen/react-jtl-plugin/lib/components/Button'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faBolt, faEnvelopeOpenDollar, faSync, faUnlock } from '@fortawesome/pro-regular-svg-icons'
import useSuccessSnack from '../../hooks/useSuccessSnack'
import Alert from '@webstollen/react-jtl-plugin/lib/components/Alert'
import Modal from "@webstollen/react-jtl-plugin/lib/components/Modal";


const Orders = () => {
  const [showSuccess] = useSuccessSnack()
  const [loading, setLoading] = useState(false)
  const api = useApi()
  const pluginInfo = usePluginInfo()
  const [showError] = useErrorSnack()
  
  const [openOrderId, setOpenOrderId] = useState<string | null>(null)
  const modalProps = Modal.useModal()

  const { loading: ordersLoading, load: loadOrders, error: ordersError, data: ordersData } = useOrders()
  const [ordersState, setOrderState] = useState({
    page: 0,
    perPage: 10,
    query: '',
  })

  useEffect(() => {
    loadOrders(ordersState.page, ordersState.perPage, ordersState.query)
  }, [loadOrders, ordersState.perPage, ordersState.page, ordersState.query])

  const reWebhook = (id: string) => {
    setLoading(true)
    fetch(pluginInfo.shopURL + `?mollie=1&id=${id}&admin=1`, {
      mode: 'no-cors',
      method: 'GET',
    })
      .then(() => fetch(pluginInfo.shopURL, { mode: 'no-cors' }).then(reload))
      .catch((e) => showError(`${e}`))
      .finally(() => setLoading(false))
  }

  const sendReminder = (id: number) => {
    setLoading(true)
    api
      .run('orders', 'reminder', { id: id })
      .then(async () => {
        showSuccess('E-Mail versendet.')
        await reload()
      })
      .catch(showError)
      .finally(() => setLoading(false))
  }

  const makeFetchable = (id: string) => {
    setLoading(true)
    api
      .run('orders', 'fetchable', {
        id: id,
      })
      .then(reload)
      .catch((e) => showError(`${e}`))
      .finally(() => setLoading(false))
  }

  const reload = useCallback(
    async () => await loadOrders(ordersState.page, ordersState.perPage, ordersState.query),
    [ordersState.page, ordersState.perPage, loadOrders, ordersState.query]
  )
  const handleTableChange = async (page: number, perPage: number) => {
    if (page === ordersState.page && perPage === ordersState.perPage) {
      await reload()
    } else {
      setOrderState((p) => ({ ...p, page: page, perPage: perPage }))
    }
  }

  const handleSearch = useCallback(
    (query: string) => {
      if (query !== ordersState.query) {
        setOrderState((p) => ({ ...p, query: query, page: 0 }))
      }
    },
    [setOrderState, ordersState.query]
  )

  const OrderTable = ordersError ? (
    <Alert variant="error">
      {ordersError}{' '}
      <TextLink color="red" onClick={() => loadOrders(ordersState.page, ordersState.perPage, ordersState.query)}>
        Erneut versuchen!
      </TextLink>
    </Alert>
  ) : (
    <DataTable
      fullWidth
      onSearch={handleSearch}
      header={header}
      loading={ordersLoading || loading}
      striped
      pagination={{
        page: ordersState.page,
        total: ordersData?.maxItems ?? 0,
        perPage: ordersState.perPage,
        onChange: handleTableChange,
      }}
    >
      {ordersData?.items && ordersData?.items.length > 0
        ? ordersData?.items.map((row: Record<string, any>) => (
            <tr key={row.cOrderId}>
              <td>
                <TextLink
                  color={'blue'}
                  onClick={() => {
                    setOpenOrderId(row.cOrderId)
                    modalProps.setIsOpen(true)
                  }}
                >
                  {row.cBestellNr}
                </TextLink>
                {!parseInt(row.bSynced) ? (
                  <abbr className="cursor-help" title="Nicht abholbar.">
                    *
                  </abbr>
                ) : null}
              </td>
              <td>
                {parseInt(row.bTest) ? (
                  <Label className={'inline mr-1'} color={'red'}>
                    TEST
                  </Label>
                ) : null}
                <pre className={'inline'}>{row.cOrderId}</pre>
              </td>
              <td className="text-center">
                {molliePaymentStatusLabel(row.fAmountRefunded === row.fAmount ? 'refunded' : row.cStatus)}
              </td>
              <td className="text-center">{jtlStatus2label(row.cJTLStatus)}</td>
              <td className="text-center">
                <PaymentMethod2img mollieMethodId={row.cMethod} style={{width: 32}}/>
              </td>
              <td className="text-right">{formatAmount(row.fAmount)}</td>
              <td className="text-center">{row.cCurrency}</td>
              <td>
                {new Date(row.dCreated).toLocaleDateString('de-DE')} {new Date(row.dCreated).toLocaleTimeString('de-DE')}
              </td>
              <td className="text-right">
                {!parseInt(row.bSynced) && (
                  <Button onClick={() => makeFetchable(row.cOrderId)} title="Abholbar machen" className={'mr-1'}>
                    <FontAwesomeIcon icon={faUnlock} />
                  </Button>
                )}

                {!['paid', 'authorized', 'completed', 'pending'].includes(row.cStatus) &&
                parseInt(row.cJTLStatus) > 0 ? (
                  <Button
                    onClick={() => sendReminder(row.kId)}
                    title={'Zahlungserinnerung ' + (row.dReminder !== null ? 'erneut ' : '') + 'verschicken'}
                    className={'mr-1'}
                  >
                    {row.dReminder !== null ? (
                      <span className="fa-layers fa-fw">
                        <FontAwesomeIcon icon={faEnvelopeOpenDollar} inverse transform="shrink-7" />
                        <FontAwesomeIcon icon={faSync} inverse transform="grow-7" />
                      </span>
                    ) : (
                      <FontAwesomeIcon icon={faEnvelopeOpenDollar} />
                    )}
                  </Button>
                ) : null}

                <Button onClick={() => reWebhook(row.cOrderId)} title="Simulate Webhook">
                  <FontAwesomeIcon icon={faBolt} />
                </Button>
              </td>
            </tr>
          ))
        : null}
    </DataTable>
  )

  return (
      <div className="relative container mx-auto">
        <Modal {...modalProps} showCloseButton={false}>
          {openOrderId && <OrderDetails id={openOrderId} onClose={modalProps.requestClose}/>}
        </Modal>
        <div className="bg-white rounded-md p-1">{OrderTable}</div>
      </div>
  )
}

export default Orders

const header: Array<DataTableHeader> = [
  {
    title: 'BestellNr',
    column: 'cBestellNr',
  },
  {
    title: 'Mollie ID',
    column: 'cOrderId',
  },
  {
    title: 'Mollie Status',
    column: 'cStatus',
  },
  {
    title: 'JTL Status',
    column: 'cJTLStatus',
  },
  {
    title: 'Methode',
    column: 'cMethod',
  },
  {
    title: 'Betrag',
    column: 'fAmount',
  },
  {
    title: 'Währung',
    column: 'cCurrency',
  },
  {
    title: 'Erstellt',
    column: 'dCreated',
  },
  {
    title: '',
    column: '_actions',
  },
]
